<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\TikTokVideoUrl;
use Source\Wiki\Shared\Domain\ValueObject\YouTubeVideoUrl;

readonly class PopularVideoReadModel
{
    public string $url;
    public string $provider;
    public string $externalVideoId;

    public function __construct(
        public string $videoLinkIdentifier,
        YouTubeVideoUrl|TikTokVideoUrl $url,
        public string $title,
        public ?string $thumbnailUrl,
        public ?int $durationSeconds,
        public int $likeCount,
        public string $language,
        public string $slug,
    ) {
        $this->url = (string) $url;
        $this->provider = $url->provider();
        $this->externalVideoId = $url->externalVideoId() ?? throw new InvalidArgumentException('A resolved video URL is required.');
    }

    /** @return array{videoLinkIdentifier:string,url:string,provider:string,externalVideoId:string,title:string,thumbnailUrl:?string,durationSeconds:?int,likeCount:int,language:string,slug:string} */
    public function toArray(): array
    {
        return [
            'videoLinkIdentifier' => $this->videoLinkIdentifier,
            'url' => $this->url,
            'provider' => $this->provider,
            'externalVideoId' => $this->externalVideoId,
            'title' => $this->title,
            'thumbnailUrl' => $this->thumbnailUrl,
            'durationSeconds' => $this->durationSeconds,
            'likeCount' => $this->likeCount,
            'language' => $this->language,
            'slug' => $this->slug,
        ];
    }
}
