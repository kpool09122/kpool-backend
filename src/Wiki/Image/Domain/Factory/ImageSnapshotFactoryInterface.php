<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Factory;

use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\Entity\ImageSnapshot;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface ImageSnapshotFactoryInterface
{
    public function create(
        Image          $image,
        WikiIdentifier $wikiIdentifier,
    ): ImageSnapshot;
}
