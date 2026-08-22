<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RequestAffiliation;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationAlreadyExistsException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Domain\Event\AffiliationRequested;
use Source\Account\Affiliation\Domain\Factory\AffiliationFactoryInterface;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;
use Source\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Domain\ValueObject\AccountIdentifier;

readonly class RequestAffiliation implements RequestAffiliationInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private AffiliationRepositoryInterface $affiliationRepository,
        private AffiliationFactoryInterface $affiliationFactory,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(RequestAffiliationInputPort $input, RequestAffiliationOutputPort $output): void
    {
        $requestingAccount = $this->accountRepository->findById($input->principal()->accountIdentifier());
        if ($requestingAccount === null) {
            throw new AccountNotFoundException('Requesting account not found.');
        }

        $requestingCategory = $requestingAccount->accountCategory();
        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::AFFILIATION_REQUEST_CREATE,
            Resource::account(
                $requestingAccount->accountIdentifier(),
                $requestingAccount->type(),
                $requestingCategory,
            ),
        )) {
            throw new DisallowedAffiliationOperationException('Affiliation request is not allowed.');
        }

        $targetAccount = $this->accountRepository->findByEmail($input->targetEmail());
        if ($targetAccount === null) {
            throw new DisallowedAffiliationOperationException('Affiliation request target is not allowed.');
        }

        $targetPrincipal = $this->principalRepository->findByEmailAndAccountIdentifier(
            $input->targetEmail(),
            $targetAccount->accountIdentifier(),
        );
        if ($targetPrincipal === null || ! $this->policyEvaluator->evaluate(
            $targetPrincipal,
            Action::AFFILIATION_REQUEST_RECEIVE,
            Resource::account(
                $targetAccount->accountIdentifier(),
                $targetAccount->type(),
                $targetAccount->accountCategory(),
                $requestingCategory,
            ),
        )) {
            throw new DisallowedAffiliationOperationException('Affiliation request target is not allowed.');
        }

        [$agencyAccountIdentifier, $talentAccountIdentifier] = $this->resolveAffiliationPair(
            $requestingCategory,
            $requestingAccount->accountIdentifier(),
            $targetAccount->accountIdentifier(),
        );

        if ($this->affiliationRepository->existsOpenAffiliation($agencyAccountIdentifier, $talentAccountIdentifier)) {
            throw new AffiliationAlreadyExistsException('An affiliation request or active affiliation already exists between these accounts.');
        }

        if ($this->affiliationRepository->findActiveByTalentAccount($talentAccountIdentifier) !== null) {
            throw new AffiliationAlreadyExistsException('The talent account already has an active affiliation.');
        }

        $affiliation = $this->affiliationFactory->create(
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestingAccount->accountIdentifier(),
            $input->terms(),
        );

        $this->affiliationRepository->save($affiliation);
        $this->eventDispatcher->dispatch(new AffiliationRequested(
            $affiliation->affiliationIdentifier(),
            $input->targetEmail(),
            $agencyAccountIdentifier,
            $talentAccountIdentifier,
            $requestingAccount->accountIdentifier(),
        ));
        $output->setAffiliation($affiliation);
    }

    /** @return array{0: AccountIdentifier, 1: AccountIdentifier} */
    private function resolveAffiliationPair(
        AccountCategory $requestingCategory,
        AccountIdentifier $requestingAccountIdentifier,
        AccountIdentifier $targetAccountIdentifier,
    ): array {
        return $requestingCategory === AccountCategory::AGENCY
            ? [$requestingAccountIdentifier, $targetAccountIdentifier]
            : [$targetAccountIdentifier, $requestingAccountIdentifier];
    }
}
