<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\Factory;

use Source\Wiki\Playlist\Domain\Entity\Playlist;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface PlaylistFactoryInterface
{
    public function create(PrincipalIdentifier $principalIdentifier, PlaylistName $name): Playlist;
}
