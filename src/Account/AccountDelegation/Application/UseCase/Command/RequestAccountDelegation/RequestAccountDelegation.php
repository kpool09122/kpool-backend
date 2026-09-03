<?php

declare(strict_types=1);

namespace Source\Account\AccountDelegation\Application\UseCase\Command\RequestAccountDelegation;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\AccountDelegation\Application\Exception\AccountDelegationForbiddenException;
use Source\Account\AccountDelegation\Application\Exception\AccountDelegationUnavailableException;
use Source\Account\AccountDelegation\Domain\Exception\AccountDelegationAlreadyExistsException;
use Source\Account\AccountDelegation\Domain\Factory\AccountDelegationFactoryInterface;
use Source\Account\AccountDelegation\Domain\Repository\AccountDelegationRepositoryInterface;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class RequestAccountDelegation implements RequestAccountDelegationInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AffiliationRepositoryInterface $affiliationRepository,
        private AccountDelegationRepositoryInterface $delegationRepository,
        private AccountDelegationFactoryInterface $delegationFactory,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(RequestAccountDelegationInputPort $input, RequestAccountDelegationOutputPort $output): void
    {
        $requestingAccount = $this->accountRepository->findById($input->principal()->accountIdentifier());
        if ($requestingAccount === null) {
            throw new AccountNotFoundException('Requesting account not found.');
        }

        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::DELEGATION_REQUEST_CREATE,
            Resource::account($requestingAccount->accountIdentifier(), $requestingAccount->type(), $requestingAccount->accountCategory()),
        )) {
            throw new AccountDelegationForbiddenException('Delegation request is not allowed.');
        }

        if ($this->accountRepository->findById($input->targetAccountIdentifier()) === null) {
            throw new AccountDelegationUnavailableException('Delegation request target is not available.');
        }

        $affiliation = $this->affiliationRepository->findActiveBetweenAccounts(
            $requestingAccount->accountIdentifier(),
            $input->targetAccountIdentifier(),
        );
        if ($affiliation === null) {
            throw new AccountDelegationUnavailableException('Delegation request target is not available.');
        }

        if ($this->delegationRepository->existsOpenByAffiliation($affiliation->affiliationIdentifier())) {
            throw new AccountDelegationAlreadyExistsException('An active delegation request already exists.');
        }

        $delegation = $this->delegationFactory->create($affiliation, $requestingAccount->accountIdentifier());
        $this->delegationRepository->save($delegation);
        $output->setDelegation($delegation);
    }
}
