<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;

interface DraftAgencyRepositoryInterface
{
    public function findById(AgencyIdentifier $agencyIdentifier): ?DraftAgency;

    public function save(DraftAgency $agency): void;

    public function delete(DraftAgency $agency): void;

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return DraftAgency[]
     */
    public function findByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array;
}
