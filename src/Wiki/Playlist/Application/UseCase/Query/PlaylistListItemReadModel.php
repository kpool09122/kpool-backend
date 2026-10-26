<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query;

readonly class PlaylistListItemReadModel
{
    public function __construct(
        public string $playlistIdentifier,
        public string $name,
        public int $videoCount,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /** @return array{playlistIdentifier:string,name:string,videoCount:int,createdAt:string,updatedAt:string} */
    public function toArray(): array
    {
        return [
            'playlistIdentifier' => $this->playlistIdentifier,
            'name' => $this->name,
            'videoCount' => $this->videoCount,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
