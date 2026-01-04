<?php

declare(strict_types=1);

namespace Source\Account\Application\UseCase\Command\RequestAffiliation;

use Source\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Application\Exception\AffiliationAlreadyExistsException;
use Source\Account\Application\Exception\InvalidAccountCategoryException;
use Source\Account\Domain\Entity\AccountAffiliation;
use Source\Account\Domain\Factory\AffiliationFactoryInterface;
use Source\Account\Domain\Repository\AccountRepositoryInterface;
use Source\Account\Domain\Repository\AffiliationRepositoryInterface;
use Source\Account\Domain\ValueObject\AccountCategory;

readonly class RequestAffiliation implements RequestAffiliationInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AffiliationRepositoryInterface $affiliationRepository,
        private AffiliationFactoryInterface $affiliationFactory,
    ) {
    }

    public function process(RequestAffiliationInputPort $input): AccountAffiliation
    {
        $agencyAccount = $this->accountRepository->findById($input->agencyAccountIdentifier());
        if ($agencyAccount === null) {
            throw new AccountNotFoundException('Agency account not found.');
        }

        if ($agencyAccount->accountCategory() !== AccountCategory::AGENCY) {
            throw new InvalidAccountCategoryException('Agency account must have agency category.');
        }

        $talentAccount = $this->accountRepository->findById($input->talentAccountIdentifier());
        if ($talentAccount === null) {
            throw new AccountNotFoundException('Talent account not found.');
        }

        if ($talentAccount->accountCategory() !== AccountCategory::TALENT) {
            throw new InvalidAccountCategoryException('Talent account must have talent category.');
        }

        if ($this->affiliationRepository->existsActiveAffiliation(
            $input->agencyAccountIdentifier(),
            $input->talentAccountIdentifier()
        )) {
            throw new AffiliationAlreadyExistsException('An active affiliation already exists between these accounts.');
        }

        $affiliation = $this->affiliationFactory->create(
            $input->agencyAccountIdentifier(),
            $input->talentAccountIdentifier(),
            $input->requestedBy(),
            $input->terms(),
        );

        $this->affiliationRepository->save($affiliation);

        return $affiliation;
    }
}
