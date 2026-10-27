<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\ExternalContentLink;

final class YouTubeVideoUrl extends ExternalContentLink
{
    private readonly string $externalVideoId;

    public function __construct(string $url)
    {
        $videoId = self::extractVideoId(trim($url));
        $this->externalVideoId = $videoId;
        parent::__construct('https://www.youtube.com/watch?v=' . $videoId);
    }

    public function provider(): string
    {
        return 'youtube';
    }

    public function externalVideoId(): string
    {
        return $this->externalVideoId;
    }

    private static function extractVideoId(string $url): string
    {
        $parts = parse_url($url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false
            || $parts === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)
        ) {
            throw new InvalidArgumentException('A valid HTTPS YouTube video URL is required.');
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $videoId = null;
        if ($host === 'youtu.be' && preg_match('~^/([^/]+)/?$~', $path, $matches) === 1) {
            $videoId = $matches[1];
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            if ($path === '/watch') {
                foreach (explode('&', $parts['query'] ?? '') as $parameter) {
                    [$key, $value] = array_pad(explode('=', $parameter, 2), 2, '');
                    if (urldecode($key) === 'v') {
                        $videoId = urldecode($value);

                        break;
                    }
                }
            } elseif (preg_match('~^/(?:embed|shorts|live)/([^/]+)/?$~', $path, $matches) === 1) {
                $videoId = $matches[1];
            }
        }
        if ($videoId === null || ! ExternalVideoId::isValidYouTubeId($videoId)) {
            throw new InvalidArgumentException('A valid YouTube video URL is required.');
        }

        return $videoId;
    }
}
