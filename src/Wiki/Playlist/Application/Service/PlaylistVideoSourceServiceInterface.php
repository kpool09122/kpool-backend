<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\Service;

use InvalidArgumentException;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItem;
use Source\Wiki\Shared\Domain\Exception\DisallowedException;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\VideoLink\Application\Exception\VideoLinkNotFoundException;

interface PlaylistVideoSourceServiceInterface
{
    /**
     * @param list<VideoLinkIdentifier> $videoLinkIdentifiers
     * @return array<string, PlaylistItem>
     * @throws VideoLinkNotFoundException
     * @throws DisallowedException
     * @throws InvalidArgumentException
     */
    public function resolveMany(array $videoLinkIdentifiers, PrincipalIdentifier $principalIdentifier, PlaylistIdentifier $playlistIdentifier): array;
}
