<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;

readonly class LikedVideoLinkReadModel
{
    public string $url;
    public string $provider;
    public string $externalVideoId;

    /** @param list<array{playlistIdentifier:string,name:string}> $playlists */
    public function __construct(
        public string $videoLinkIdentifier,
        YouTubeVideoUrl|TikTokVideoUrl $url,
        public string $title,
        public ?string $thumbnailUrl,
        public ?int $durationSeconds,
        public string $language,
        public string $resourceType,
        public string $slug,
        public string $likedAt,
        public array $playlists,
        public bool $isOfficialOwnerLike,
    ) {
        $this->url = (string) $url;
        $this->provider = $url->provider();
        $this->externalVideoId = $url->externalVideoId() ?? throw new InvalidArgumentException('A resolved video URL is required.');
    }

    /** @return array{provider:string,externalVideoId:string,videoLinkIdentifier:string,url:string,title:string,thumbnailUrl:?string,durationSeconds:?int,language:string,resourceType:string,slug:string,likedAt:string,playlists:list<array{playlistIdentifier:string,name:string}>,isOfficialOwnerLike:bool} */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'externalVideoId' => $this->externalVideoId,
            'videoLinkIdentifier' => $this->videoLinkIdentifier,
            'url' => $this->url,
            'title' => $this->title,
            'thumbnailUrl' => $this->thumbnailUrl,
            'durationSeconds' => $this->durationSeconds,
            'language' => $this->language,
            'resourceType' => $this->resourceType,
            'slug' => $this->slug,
            'likedAt' => $this->likedAt,
            'playlists' => $this->playlists,
            'isOfficialOwnerLike' => $this->isOfficialOwnerLike,
        ];
    }
}
