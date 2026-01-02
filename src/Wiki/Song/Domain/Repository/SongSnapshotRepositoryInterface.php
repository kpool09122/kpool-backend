<?php

declare(strict_types=1);

namespace Source\Wiki\Song\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Song\Domain\Entity\SongSnapshot;
use Source\Wiki\Song\Domain\ValueObject\SongIdentifier;

interface SongSnapshotRepositoryInterface
{
    public function save(SongSnapshot $snapshot): void;

    /**
     * @param SongIdentifier $songIdentifier
     * @return SongSnapshot[]
     */
    public function findBySongIdentifier(SongIdentifier $songIdentifier): array;

    public function findBySongAndVersion(
        SongIdentifier $songIdentifier,
        Version $version
    ): ?SongSnapshot;

    /**
     * @return SongSnapshot[]
     */
    public function findByTranslationSetIdentifierAndVersion(
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version
    ): array;
}
