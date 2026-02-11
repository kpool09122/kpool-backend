<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Service;

use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface ImageAuthorizationResourceBuilderInterface
{
    public function buildFromDraftResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): Resource;

    public function buildFromDraftImage(DraftImage $draftImage): Resource;

    public function buildFromImage(Image $image): Resource;
}
