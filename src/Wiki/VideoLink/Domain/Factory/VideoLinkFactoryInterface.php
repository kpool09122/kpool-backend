<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Factory;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;

interface VideoLinkFactoryInterface
{
    public function create(
        ResourceType $resourceType,
        ResourceIdentifier $resourceIdentifier,
        ExternalContentLink $url,
        VideoUsage $videoUsage,
        string $title,
        int $displayOrder,
    ): VideoLink;
}
