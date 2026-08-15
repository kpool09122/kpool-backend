<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\ApproveAffiliation;

use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Event\AffiliationActivated;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Shared\Application\Service\Event\EventDispatcherInterface;

readonly class ApproveAffiliation implements ApproveAffiliationInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private AffiliationRepositoryInterface $affiliationRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function process(ApproveAffiliationInputPort $input, ApproveAffiliationOutputPort $output): void
    {
        $affiliation = $this->affiliationRepository->findById($input->affiliationIdentifier());

        if ($affiliation === null) {
            throw new AffiliationNotFoundException('Affiliation not found.');
        }

        if ((string) $affiliation->approverAccountIdentifier() !== (string) $input->principal()->accountIdentifier()) {
            throw new DisallowedAffiliationOperationException('Only the designated approver can approve this affiliation.');
        }

        $agencyAccount = $this->accountRepository->findById($affiliation->agencyAccountIdentifier());
        $talentAccount = $this->accountRepository->findById($affiliation->talentAccountIdentifier());
        if ($agencyAccount === null || $talentAccount === null) {
            throw new DisallowedAffiliationOperationException('Affiliation approval is not allowed.');
        }

        $approverAccount = (string) $affiliation->approverAccountIdentifier() === (string) $agencyAccount->accountIdentifier()
            ? $agencyAccount
            : $talentAccount;

        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::AFFILIATION_APPROVE,
            Resource::account(
                $approverAccount->accountIdentifier(),
                $approverAccount->type(),
                $approverAccount->accountCategory(),
                $this->requestingAccountCategory($affiliation),
            ),
        )) {
            throw new DisallowedAffiliationOperationException('Affiliation approval is not allowed.');
        }

        $activeAffiliation = $this->affiliationRepository->findActiveByTalentAccount($affiliation->talentAccountIdentifier());
        if ($activeAffiliation !== null
            && (string) $activeAffiliation->affiliationIdentifier() !== (string) $affiliation->affiliationIdentifier()) {
            throw new DisallowedAffiliationOperationException('The talent account already has an active affiliation.');
        }

        $affiliation->approve();

        $this->affiliationRepository->save($affiliation);

        $this->eventDispatcher->dispatch(new AffiliationActivated(
            $affiliation->affiliationIdentifier(),
            $affiliation->agencyAccountIdentifier(),
            $affiliation->talentAccountIdentifier(),
            $affiliation->activatedAt(),
            (string) $agencyAccount->name(),
            (string) $talentAccount->name(),
            $agencyAccount->type(),
            $talentAccount->type(),
        ));
        $output->setAffiliation($affiliation);
    }

    private function requestingAccountCategory(Affiliation $affiliation): AccountCategory
    {
        return $affiliation->isRequestedByAgency() ? AccountCategory::AGENCY : AccountCategory::TALENT;
    }
}
