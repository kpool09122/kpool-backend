<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Factory;

use Source\Shared\Domain\ValueObject\ExternalContentLink;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface VideoLinkFactoryInterface
{
    public function create(
        ResourceType        $resourceType,
        WikiIdentifier      $wikiIdentifier,
        ExternalContentLink $url,
        VideoUsage          $videoUsage,
        string              $title,
        int                 $displayOrder,
    ): VideoLink;
}
