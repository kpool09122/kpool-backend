<?php

declare(strict_types=1);

namespace Application\Http\Client\GeminiClient;

final readonly class GeneratedYouTubeVideo
{
    private function __construct(private string $id)
    {
    }

    public static function fromUrl(mixed $url): ?self
    {
        if (! is_string($url) || preg_match('~\Ahttps://(?:www\.)?youtube\.com/watch\?v=([A-Za-z0-9_-]{11})\z~', $url, $matches) !== 1) {
            return null;
        }

        return new self($matches[1]);
    }

    public function id(): string
    {
        return $this->id;
    }
}
