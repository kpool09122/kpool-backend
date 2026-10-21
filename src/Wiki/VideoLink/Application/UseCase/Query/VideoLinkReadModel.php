<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query;

readonly class VideoLinkReadModel
{
    public function __construct(
        public string $videoLinkIdentifier,
        public string $url,
        public string $title,
        public ?string $thumbnailUrl,
        public ?string $publishedAt,
        public int $displayOrder,
        public string $videoUsage,
    ) {
    }

    /** @return array{videoLinkIdentifier:string,url:string,title:string,thumbnailUrl:?string,publishedAt:?string,displayOrder:int,videoUsage:string} */
    public function toArray(): array
    {
        return [
            'videoLinkIdentifier' => $this->videoLinkIdentifier,
            'url' => $this->url,
            'title' => $this->title,
            'thumbnailUrl' => $this->thumbnailUrl,
            'publishedAt' => $this->publishedAt,
            'displayOrder' => $this->displayOrder,
            'videoUsage' => $this->videoUsage,
        ];
    }
}
