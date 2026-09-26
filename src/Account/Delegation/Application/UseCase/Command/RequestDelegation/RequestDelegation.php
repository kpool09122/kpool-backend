<?php

declare(strict_types=1);

namespace Source\Account\Delegation\Application\UseCase\Command\RequestDelegation;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Delegation\Application\Exception\DelegationNotAllowedException;
use Source\Account\Delegation\Application\Exception\DelegationUnavailableException;
use Source\Account\Delegation\Domain\Exception\DelegationAlreadyExistsException;
use Source\Account\Delegation\Domain\Factory\DelegationFactoryInterface;
use Source\Account\Delegation\Domain\Repository\DelegationRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;

readonly class RequestDelegation implements RequestDelegationInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AffiliationRepositoryInterface $affiliationRepository,
        private DelegationRepositoryInterface $delegationRepository,
        private DelegationFactoryInterface $delegationFactory,
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
            throw new DelegationNotAllowedException();
        }

        if ($this->accountRepository->findById($input->targetAccountIdentifier()) === null) {
            throw new DelegationUnavailableException();
        }

        $affiliation = $this->affiliationRepository->findActiveBetweenAccounts(
            $requestingAccount->accountIdentifier(),
            $input->targetAccountIdentifier(),
        );
        if ($affiliation === null) {
            throw new DelegationUnavailableException();
        }

        if ($this->delegationRepository->findOpenByAffiliationId($affiliation->affiliationIdentifier()) !== null) {
            throw new DelegationAlreadyExistsException();
        }

        $delegation = $this->delegationFactory->create($affiliation, $requestingAccount->accountIdentifier());
        $this->delegationRepository->save($delegation);
        $output->setDelegation($delegation);
    }
}
