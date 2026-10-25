<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Domain\Repository;

use Source\Wiki\Playlist\Domain\Entity\Playlist;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface PlaylistRepositoryInterface
{
    public function countByPrincipal(PrincipalIdentifier $principalIdentifier): int;

    public function findByIdAndPrincipal(PlaylistIdentifier $playlistIdentifier, PrincipalIdentifier $principalIdentifier): ?Playlist;

    public function save(Playlist $playlist): void;

    public function deleteByIdAndPrincipal(PlaylistIdentifier $playlistIdentifier, PrincipalIdentifier $principalIdentifier): void;
}
