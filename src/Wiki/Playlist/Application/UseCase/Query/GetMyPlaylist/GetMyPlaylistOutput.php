<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query\GetMyPlaylist;

use Source\Wiki\Playlist\Application\UseCase\Query\PlaylistDetailReadModel;

class GetMyPlaylistOutput implements GetMyPlaylistOutputPort
{
    private ?PlaylistDetailReadModel $playlist = null;

    public function output(PlaylistDetailReadModel $playlist): void
    {
        $this->playlist = $playlist;
    }

    /** @return array{playlistIdentifier:string,name:string,videoCount:int,createdAt:string,updatedAt:string,items:list<array{sourceVideoLinkIdentifier:string|null,provider:string,externalVideoId:string,position:int,url:string,title:string,thumbnailUrl:string|null,durationSeconds:int|null}>}|array{} */
    public function toArray(): array
    {
        return $this->playlist?->toArray() ?? [];
    }
}
