<?php

declare(strict_types=1);

namespace Source\Wiki\Playlist\Application\UseCase\Query;

readonly class PlaylistItemReadModel
{
    public function __construct(
        public string $playlistItemIdentifier,
        public ?string $sourceVideoLinkIdentifier,
        public string $provider,
        public string $externalVideoId,
        public int $position,
        public string $url,
        public string $title,
        public ?string $thumbnailUrl,
        public ?int $durationSeconds,
    ) {
    }

    /** @return array{playlistItemIdentifier:string,sourceVideoLinkIdentifier:string|null,provider:string,externalVideoId:string,position:int,url:string,title:string,thumbnailUrl:string|null,durationSeconds:int|null} */
    public function toArray(): array
    {
        return [
            'playlistItemIdentifier' => $this->playlistItemIdentifier,
            'sourceVideoLinkIdentifier' => $this->sourceVideoLinkIdentifier,
            'provider' => $this->provider,
            'externalVideoId' => $this->externalVideoId,
            'position' => $this->position,
            'url' => $this->url,
            'title' => $this->title,
            'thumbnailUrl' => $this->thumbnailUrl,
            'durationSeconds' => $this->durationSeconds,
        ];
    }
}
