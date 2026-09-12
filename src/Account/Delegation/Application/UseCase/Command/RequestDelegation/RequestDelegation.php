<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Delegation\Application\Exception\AccountDelegationNotAllowedException;
use Source\Account\Delegation\Application\Exception\AccountDelegationUnavailableException;
use Source\Account\Delegation\Domain\Exception\AccountDelegationAlreadyExistsException;
use Source\Account\Delegation\Domain\Factory\AccountDelegationFactoryInterface;
use Source\Account\Delegation\Domain\Repository\AccountDelegationRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class RequestDelegation implements RequestDelegationInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AffiliationRepositoryInterface $affiliationRepository,
        private AccountDelegationRepositoryInterface $delegationRepository,
        private AccountDelegationFactoryInterface $delegationFactory,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(RequestDelegationInputPort $input, RequestDelegationOutputPort $output): void
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
            throw new AccountDelegationNotAllowedException();
        }

        if ($this->accountRepository->findById($input->targetAccountIdentifier()) === null) {
            throw new AccountDelegationUnavailableException();
        }

        $affiliation = $this->affiliationRepository->findActiveBetweenAccounts(
            $requestingAccount->accountIdentifier(),
            $input->targetAccountIdentifier(),
        );
        if ($affiliation === null) {
            throw new AccountDelegationUnavailableException();
        }

        if ($this->delegationRepository->findOpenByAffiliationId($affiliation->affiliationIdentifier()) !== null) {
            throw new AccountDelegationAlreadyExistsException();
        }

        $delegation = $this->delegationFactory->create($affiliation, $requestingAccount->accountIdentifier());
        $this->delegationRepository->save($delegation);
        $output->setDelegation($delegation);
    }
}
