<?php

declare(strict_types=1);

namespace Source\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\ExternalContentLink;

final class TikTokVideoUrl extends ExternalContentLink
{
    private readonly ?string $externalVideoId;

    public function __construct(string $url)
    {
        $url = trim($url);
        $parts = parse_url($url);
        if ($parts === false || filter_var($url, FILTER_VALIDATE_URL) === false
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || (isset($parts['port']) && $parts['port'] !== 443)
            || ! self::hasSupportedHost($url)) {
            throw new InvalidArgumentException('A valid HTTPS TikTok video URL is required.');
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        if (in_array($host, ['vm.tiktok.com', 'vt.tiktok.com'], true)) {
            if (preg_match('~^/[a-zA-Z0-9]+/?$~D', $path) !== 1) {
                throw new InvalidArgumentException('A TikTok short video URL is required.');
            }
            $this->externalVideoId = null;
            parent::__construct('https://' . $host . $path . (isset($parts['query']) ? '?' . $parts['query'] : ''));

            return;
        }

        if (preg_match('~^/@([a-zA-Z0-9_.]{1,24})/video/([^/]+)/?$~D', $path, $matches) !== 1
            || ! ExternalVideoId::isValidTikTokId($matches[2])) {
            throw new InvalidArgumentException('A TikTok video URL is required.');
        }
        $this->externalVideoId = $matches[2];
        parent::__construct('https://www.tiktok.com/@' . $matches[1] . '/video/' . $matches[2]);
    }

    public static function hasSupportedHost(string $url): bool
    {
        return in_array(strtolower((string) parse_url(trim($url), PHP_URL_HOST)), ['tiktok.com', 'www.tiktok.com', 'vm.tiktok.com', 'vt.tiktok.com'], true);
    }

    public function provider(): string
    {
        return 'tiktok';
    }

    public function externalVideoId(): ?string
    {
        return $this->externalVideoId;
    }
}
