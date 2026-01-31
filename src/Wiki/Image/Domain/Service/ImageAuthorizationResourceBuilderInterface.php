<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Service;

use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface ImageAuthorizationResourceBuilderInterface
{
    public function buildFromDraftResource(ResourceType $resourceType, ResourceIdentifier $draftResourceIdentifier): Resource;

    public function buildFromDraftImage(DraftImage $draftImage): Resource;

    public function buildFromImage(Image $image): Resource;
}
