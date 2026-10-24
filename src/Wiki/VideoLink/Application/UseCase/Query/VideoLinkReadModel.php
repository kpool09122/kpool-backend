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
        public string $videoUsage,
        public ?int $durationSeconds,
    ) {
    }

    /** @return array{videoLinkIdentifier:string,url:string,title:string,thumbnailUrl:?string,publishedAt:?string,videoUsage:string,durationSeconds:?int} */
    public function toArray(): array
    {
        return [
            'videoLinkIdentifier' => $this->videoLinkIdentifier,
            'url' => $this->url,
            'title' => $this->title,
            'thumbnailUrl' => $this->thumbnailUrl,
            'publishedAt' => $this->publishedAt,
            'videoUsage' => $this->videoUsage,
            'durationSeconds' => $this->durationSeconds,
        ];
    }
}
