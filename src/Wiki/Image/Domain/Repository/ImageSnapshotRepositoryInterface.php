<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Domain\Entity\ImageSnapshot;
use Source\Wiki\Image\Domain\ValueObject\ImageSnapshotIdentifier;

interface ImageSnapshotRepositoryInterface
{
    public function findById(ImageSnapshotIdentifier $identifier): ?ImageSnapshot;

    /**
     * @return ImageSnapshot[]
     */
    public function findByResourceSnapshot(TranslationSetIdentifier $translationSetIdentifier): array;

    public function save(ImageSnapshot $imageSnapshot): void;
}
