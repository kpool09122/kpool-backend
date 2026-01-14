<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Factory;

use Source\Shared\Domain\ValueObject\ImagePath;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Image\Domain\ValueObject\ImageUsage;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface DraftImageFactoryInterface
{
    public function create(
        ?ImageIdentifier $publishedImageIdentifier,
        ResourceType $resourceType,
        ResourceIdentifier $draftResourceIdentifier,
        PrincipalIdentifier $editorIdentifier,
        ImagePath $imagePath,
        ImageUsage $imageUsage,
        int $displayOrder,
    ): DraftImage;
}
