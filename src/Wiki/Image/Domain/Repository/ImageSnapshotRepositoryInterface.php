<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Repository;

use Source\Wiki\Image\Domain\Entity\ImageSnapshot;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface ImageSnapshotRepositoryInterface
{
    public function findById(ImageSnapshotIdentifier $identifier): ?ImageSnapshot;

    /**
     * @return ImageSnapshot[]
     */
    public function findByResourceSnapshot(WikiIdentifier $wikiIdentifier): array;

    public function save(ImageSnapshot $imageSnapshot): void;
}
