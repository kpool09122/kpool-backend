<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\Entity\WikiSnapshot;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface WikiSnapshotRepositoryInterface
{
    public function save(WikiSnapshot $snapshot): void;

    /**
     * All revisions, ordered by version, creation time and ID descending.
     * @return WikiSnapshot[]
     */
    public function findByWikiIdentifier(WikiIdentifier $wikiIdentifier): array;

    /** Latest version, then creation time descending, then snapshot ID descending. */
    public function findLatestByWikiIdentifier(WikiIdentifier $wikiIdentifier): ?WikiSnapshot;

    /** Latest creation time, then snapshot ID descending, within the requested version. */
    public function findByWikiAndVersion(
        WikiIdentifier $wikiIdentifier,
        Version $version,
    ): ?WikiSnapshot;

    /**
     * One latest revision per Wiki, using creation time then ID descending.
     * Results are ordered by Wiki ID ascending.
     * @return WikiSnapshot[]
     */
    public function findByTranslationSetIdentifierAndVersion(
        TranslationSetIdentifier $translationSetIdentifier,
        Version $version,
    ): array;

    /**
     * @return WikiSnapshot[]
     */
    public function findByImageIdentifier(ImageIdentifier $imageIdentifier): array;
}
