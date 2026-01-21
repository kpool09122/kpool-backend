<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLinkAutoCollection\Domain\ValueObject;

use DateTimeImmutable;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoUsage;

readonly class YouTubeVideoInfo
{
    public function __construct(
        private string $videoId,
        private string $title,
        private string $url,
        private string $thumbnailUrl,
        private VideoUsage $videoUsage,
        private DateTimeImmutable $publishedAt,
    ) {
    }

    public function videoId(): string
    {
        return $this->videoId;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function thumbnailUrl(): string
    {
        return $this->thumbnailUrl;
    }

    public function videoUsage(): VideoUsage
    {
        return $this->videoUsage;
    }

    public function publishedAt(): DateTimeImmutable
    {
        return $this->publishedAt;
    }
}
