<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\TalentSnapshot;

interface TalentSnapshotRepositoryInterface
{
    public function save(TalentSnapshot $snapshot): void;

    /**
     * @param TalentIdentifier $talentIdentifier
     * @return TalentSnapshot[]
     */
    public function findByTalentIdentifier(TalentIdentifier $talentIdentifier): array;

    public function findByTalentAndVersion(
        TalentIdentifier $talentIdentifier,
        Version $version
    ): ?TalentSnapshot;

    /**
     * @return TalentSnapshot[]
     */
    public function findByTranslationSetIdentifierAndVersion(
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version
    ): array;
}
