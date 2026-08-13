<?php

declare(strict_types=1);

namespace Source\Account\Affiliation\Application\UseCase\Command\RejectAffiliation;

use Source\Account\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Affiliation\Application\Exception\AffiliationNotFoundException;
use Source\Account\Affiliation\Application\Exception\DisallowedAffiliationOperationException;
use Source\Account\Affiliation\Domain\Entity\Affiliation;
use Source\Account\Affiliation\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Account\Principal\Domain\ValueObject\Action;
use Source\Account\Principal\Domain\ValueObject\Resource;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;

readonly class RejectAffiliation implements RejectAffiliationInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
        private AffiliationRepositoryInterface $affiliationRepository,
    ) {
    }

    public function process(RejectAffiliationInputPort $input): void
    {
        $affiliation = $this->affiliationRepository->findById($input->affiliationIdentifier());

        if ($affiliation === null) {
            throw new AffiliationNotFoundException('Affiliation not found.');
        }

        if (! $affiliation->isPending()) {
            throw new DisallowedAffiliationOperationException('Only pending affiliations can be rejected.');
        }

        if ((string) $affiliation->approverAccountIdentifier() !== (string) $input->principal()->accountIdentifier()) {
            throw new DisallowedAffiliationOperationException('Only the designated approver can reject this affiliation.');
        }

        $approverAccount = $this->accountRepository->findById($affiliation->approverAccountIdentifier());
        if ($approverAccount === null) {
            throw new DisallowedAffiliationOperationException('Affiliation rejection is not allowed.');
        }

        if (! $this->policyEvaluator->evaluate(
            $input->principal(),
            Action::AFFILIATION_REJECT,
            Resource::account(
                $approverAccount->accountIdentifier(),
                $approverAccount->type(),
                $approverAccount->accountCategory(),
                $this->requestingAccountCategory($affiliation),
            ),
        )) {
            throw new DisallowedAffiliationOperationException('Affiliation rejection is not allowed.');
        }

        $this->affiliationRepository->delete($affiliation);
    }

    private function requestingAccountCategory(Affiliation $affiliation): AccountCategory
    {
        return $affiliation->isRequestedByAgency() ? AccountCategory::AGENCY : AccountCategory::TALENT;
    }
}
