<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Factory;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Entity\ImageSnapshot;

interface ImageSnapshotFactoryInterface
{
    public function create(
        Image          $image,
        TranslationSetIdentifier $translationSetIdentifier,
    ): ImageSnapshot;
}
