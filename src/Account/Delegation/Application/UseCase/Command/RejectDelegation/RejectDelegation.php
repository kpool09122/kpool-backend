<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RejectDelegation;

use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Delegation\Application\Exception\DelegationNotFoundException;
use Source\Account\Delegation\Application\Exception\DisallowedDelegationOperationException;
use Source\Account\Delegation\Domain\Repository\AccountDelegationRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class RejectDelegation implements RejectDelegationInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccountDelegationRepositoryInterface $delegationRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(RejectDelegationInputPort $input): void
    {
        $delegation = $this->delegationRepository->findById($input->delegationIdentifier());
        if ($delegation === null) {
            throw new DelegationNotFoundException('Delegation not found.');
        }
        if (! $delegation->isPending()) {
            throw new DisallowedDelegationOperationException('Only pending delegations can be rejected.');
        }
        if ((string) $delegation->delegatorAccountIdentifier() !== (string) $input->principal()->accountIdentifier()) {
            throw new DisallowedDelegationOperationException('Only the delegator account can reject this delegation.');
        }
        $account = $this->accountRepository->findById($delegation->delegatorAccountIdentifier());
        if ($account === null || ! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::DELEGATION_REJECT,
            Resource::account($account->accountIdentifier(), $account->type(), $account->accountCategory()),
        )) {
            throw new DisallowedDelegationOperationException('Delegation rejection is not allowed.');
        }
        $this->delegationRepository->delete($delegation);
    }
}
