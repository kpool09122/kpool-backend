<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;

readonly class VideoLinkReadModel
{
    public string $url;
    public string $provider;
    public string $externalVideoId;

    public function __construct(
        public string $videoLinkIdentifier,
        YouTubeVideoUrl|TikTokVideoUrl $url,
        public string $title,
        public ?string $thumbnailUrl,
        public ?string $publishedAt,
        public string $videoUsage,
        public ?int $durationSeconds,
        public ?string $creatorPrincipalIdentifier,
        public ?string $creatorIdentityName,
        public int $likeCount,
        public bool $hasOfficialOwnerLike,
        public bool $hasOfficialOwnerWatch,
    ) {
        $this->url = (string) $url;
        $this->provider = $url->provider();
        $this->externalVideoId = $url->externalVideoId() ?? throw new InvalidArgumentException('A resolved video URL is required.');
    }

    /** @return array{provider:string,externalVideoId:string,videoLinkIdentifier:string,url:string,title:string,thumbnailUrl:?string,publishedAt:?string,videoUsage:string,durationSeconds:?int,creatorPrincipalIdentifier:?string,creatorIdentityName:?string,likeCount:int,hasOfficialOwnerLike:bool,hasOfficialOwnerWatch:bool} */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'externalVideoId' => $this->externalVideoId,
            'videoLinkIdentifier' => $this->videoLinkIdentifier,
            'url' => $this->url,
            'title' => $this->title,
            'thumbnailUrl' => $this->thumbnailUrl,
            'publishedAt' => $this->publishedAt,
            'videoUsage' => $this->videoUsage,
            'durationSeconds' => $this->durationSeconds,
            'creatorPrincipalIdentifier' => $this->creatorPrincipalIdentifier,
            'creatorIdentityName' => $this->creatorIdentityName,
            'likeCount' => $this->likeCount,
            'hasOfficialOwnerLike' => $this->hasOfficialOwnerLike,
            'hasOfficialOwnerWatch' => $this->hasOfficialOwnerWatch,
        ];
    }
}
