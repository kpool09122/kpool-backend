<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Domain\Service;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

final class VideoLinkCollectionPolicy
{
    public static function supports(ResourceType $resourceType): bool
    {
        return in_array($resourceType, [ResourceType::GROUP, ResourceType::SONG, ResourceType::TALENT], true);
    }
}
