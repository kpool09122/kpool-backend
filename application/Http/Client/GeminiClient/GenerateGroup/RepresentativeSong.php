<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient\GenerateGroup;

use Application\Http\Client\GeminiClient\GeneratedYouTubeVideo;

final readonly class RepresentativeSong
{
    private function __construct(
        private string $title,
        private ?string $youtubeVideoId,
    ) {
    }

    public static function fromValue(mixed $value): ?self
    {
        $title = is_string($value) ? $value : (is_array($value) ? ($value['title'] ?? null) : null);
        if (! is_string($title) || trim($title) === '') {
            return null;
        }

        $url = is_array($value) ? ($value['official_mv_url'] ?? null) : null;

        return new self(trim($title), GeneratedYouTubeVideo::fromUrl($url)?->id());
    }

    public function title(): string
    {
        return $this->title;
    }

    public function youtubeVideoId(): ?string
    {
        return $this->youtubeVideoId;
    }
}
