<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateGroup;

use Application\Http\Client\GeminiClient\GeneratedYouTubeVideo;

final readonly class RepresentativeSong
{
    private function __construct(
        private string $title,
        private string $youtubeVideoId,
    ) {
    }

    public static function fromValue(mixed $value): ?self
    {
        if (! is_array($value)) {
            return null;
        }

        $title = $value['title'] ?? null;
        if (! is_string($title) || trim($title) === '') {
            return null;
        }

        $video = GeneratedYouTubeVideo::fromUrl($value['official_mv_url'] ?? null);

        return $video === null ? null : new self(trim($title), $video->id());
    }

    public function title(): string
    {
        return $this->title;
    }

    public function youtubeVideoId(): string
    {
        return $this->youtubeVideoId;
    }
}
