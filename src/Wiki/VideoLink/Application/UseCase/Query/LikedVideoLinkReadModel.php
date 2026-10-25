<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query;

readonly class LikedVideoLinkReadModel
{
    public function __construct(
        public string $videoLinkIdentifier,
        public string $url,
        public string $title,
        public ?string $thumbnailUrl,
        public ?int $durationSeconds,
        public string $language,
        public string $resourceType,
        public string $slug,
        public string $likedAt,
    ) {
    }

    /** @return array{videoLinkIdentifier:string,url:string,title:string,thumbnailUrl:?string,durationSeconds:?int,language:string,resourceType:string,slug:string,likedAt:string} */
    public function toArray(): array
    {
        return [
            'videoLinkIdentifier' => $this->videoLinkIdentifier,
            'url' => $this->url,
            'title' => $this->title,
            'thumbnailUrl' => $this->thumbnailUrl,
            'durationSeconds' => $this->durationSeconds,
            'language' => $this->language,
            'resourceType' => $this->resourceType,
            'slug' => $this->slug,
            'likedAt' => $this->likedAt,
        ];
    }
}
