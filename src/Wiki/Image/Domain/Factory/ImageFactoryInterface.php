<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Factory;

use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface ImageFactoryInterface
{
    public function create(
        ResourceType $resourceType,
        ResourceIdentifier $resourceIdentifier,
        ImagePath $imagePath,
        ImageUsage $imageUsage,
        int $displayOrder,
    ): Image;
}
