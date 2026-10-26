<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query\ListMyPlaylists;

use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistListItemReadModel;

class ListMyPlaylistsOutput implements ListMyPlaylistsOutputPort
{
    /** @var list<PlaylistListItemReadModel> */
    private array $playlists = [];

    /** @param list<PlaylistListItemReadModel> $playlists */
    public function output(array $playlists): void
    {
        $this->playlists = $playlists;
    }

    /** @return array{playlists:list<array{playlistIdentifier:string,name:string,videoCount:int,createdAt:string,updatedAt:string}>} */
    public function toArray(): array
    {
        return ['playlists' => array_map(static fn (PlaylistListItemReadModel $playlist): array => $playlist->toArray(), $this->playlists)];
    }
}
