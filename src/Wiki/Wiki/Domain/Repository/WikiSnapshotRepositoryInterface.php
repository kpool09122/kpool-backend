<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface WikiSnapshotRepositoryInterface
{
    public function save(WikiSnapshot $snapshot): void;

    /**
     * @return WikiSnapshot[]
     */
    public function findByWikiIdentifier(WikiIdentifier $wikiIdentifier): array;

    public function findByWikiAndVersion(
        WikiIdentifier $wikiIdentifier,
        Version $version,
    ): ?WikiSnapshot;

    /**
     * @return WikiSnapshot[]
     */
    public function findByTranslationSetIdentifierAndVersion(
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
    ): array;
}
