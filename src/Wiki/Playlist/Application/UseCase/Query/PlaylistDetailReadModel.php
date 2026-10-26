<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query;

readonly class PlaylistDetailReadModel
{
    /** @param list<PlaylistItemReadModel> $items */
    public function __construct(
        public string $playlistIdentifier,
        public string $name,
        public string $createdAt,
        public string $updatedAt,
        public array $items,
    ) {
    }

    /** @return array{playlistIdentifier:string,name:string,videoCount:int,createdAt:string,updatedAt:string,items:list<array{playlistItemIdentifier:string,sourceVideoLinkIdentifier:string|null,provider:string,externalVideoId:string,position:int,url:string,title:string,thumbnailUrl:string|null,durationSeconds:int|null}>} */
    public function toArray(): array
    {
        return [
            'playlistIdentifier' => $this->playlistIdentifier,
            'name' => $this->name,
            'videoCount' => count($this->items),
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'items' => array_map(static fn (PlaylistItemReadModel $item): array => $item->toArray(), $this->items),
        ];
    }
}
