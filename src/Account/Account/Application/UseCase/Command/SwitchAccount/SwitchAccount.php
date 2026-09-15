<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\SwitchAccount;

use Source\Account\Account\Application\Service\CurrentAccount;
use Source\Account\Account\Application\Service\CurrentAccountServiceInterface;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Delegation\Application\Exception\DelegationNotFoundException;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Principal\Domain\Factory\PrincipalFactoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalGroupRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class SwitchAccount implements SwitchAccountInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private DelegationRepositoryInterface $delegationRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PrincipalFactoryInterface $principalFactory,
        private PrincipalGroupRepositoryInterface $principalGroupRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private CurrentAccountServiceInterface $currentAccountService,
    ) {
    }

    public function process(SwitchAccountInputPort $input, SwitchAccountOutputPort $output): void
    {
        $originalPrincipal = $this->principalRepository->findById($input->originalPrincipalIdentifier());
        if ($originalPrincipal === null
            || (string) $originalPrincipal->identityIdentifier() !== (string) $input->identityIdentifier()) {
            throw new DisallowedDelegationOperationException('Original account principal is not valid.');
        }

        $targetDelegationIdentifier = $input->targetDelegationIdentifier();

        if ($targetDelegationIdentifier === null) {
            $currentAccount = new CurrentAccount(
                originalIdentityIdentifier: $input->identityIdentifier(),
                originalAccountIdentifier: $originalPrincipal->accountIdentifier(),
                originalPrincipalIdentifier: $originalPrincipal->principalIdentifier(),
                effectiveAccountIdentifier: $originalPrincipal->accountIdentifier(),
                effectivePrincipalIdentifier: $originalPrincipal->principalIdentifier(),
                delegationIdentifier: null,
            );

            $this->currentAccountService->save($currentAccount);
            $output->setCurrentAccount($currentAccount);

            return;
        }

        $delegation = $this->delegationRepository->findById($targetDelegationIdentifier);
        if ($delegation === null) {
            throw new DelegationNotFoundException('Delegation not found.');
        }
        if (! $delegation->isApproved()) {
            throw new DisallowedDelegationOperationException('Only approved delegations can be switched.');
        }
        if ((string) $delegation->delegateAccountIdentifier() !== (string) $originalPrincipal->accountIdentifier()) {
            throw new DisallowedDelegationOperationException('Only the delegate account can switch this delegation.');
        }

        $delegateAccount = $this->accountRepository->findById($delegation->delegateAccountIdentifier());
        if ($delegateAccount === null || ! $this->policyEvaluator->evaluate(
            $originalPrincipal,
            Action::DELEGATION_ACCOUNT_SWITCH,
            Resource::account($delegateAccount->accountIdentifier(), $delegateAccount->type(), $delegateAccount->accountCategory()),
        )) {
            throw new DisallowedDelegationOperationException('Delegated account switch is not allowed.');
        }

        $effectivePrincipal = $this->principalRepository->findByIdentityIdentifierAndAccountIdentifier(
            $input->identityIdentifier(),
            $delegation->delegatorAccountIdentifier(),
        );
        if ($effectivePrincipal === null) {
            $effectivePrincipal = $this->principalFactory->create($input->identityIdentifier(), $delegation->delegatorAccountIdentifier());
            $this->principalRepository->save($effectivePrincipal);

            $defaultGroup = $this->principalGroupRepository->findDefaultByAccountId($delegation->delegatorAccountIdentifier());
            if ($defaultGroup !== null && ! $defaultGroup->hasMember($effectivePrincipal->principalIdentifier())) {
                $defaultGroup->addMember($effectivePrincipal->principalIdentifier());
                $this->principalGroupRepository->save($defaultGroup);
            }
        }

        $currentAccount = new CurrentAccount(
            originalIdentityIdentifier: $input->identityIdentifier(),
            originalAccountIdentifier: $originalPrincipal->accountIdentifier(),
            originalPrincipalIdentifier: $originalPrincipal->principalIdentifier(),
            effectiveAccountIdentifier: $effectivePrincipal->accountIdentifier(),
            effectivePrincipalIdentifier: $effectivePrincipal->principalIdentifier(),
            delegationIdentifier: $delegation->delegationIdentifier(),
        );

        $this->currentAccountService->save($currentAccount);
        $output->setCurrentAccount($currentAccount);
    }
}
