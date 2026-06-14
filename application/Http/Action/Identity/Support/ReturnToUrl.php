<?php

declare(strict_types=1);

namespace Application\Http\Action\Identity\Support;

readonly class ReturnToUrl
{
    public static function normalize(?string $returnTo): ?string
    {
        if ($returnTo === null || trim($returnTo) === '') {
            return null;
        }

        $returnTo = trim($returnTo);

        if (str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return $returnTo;
        }

        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');
        $frontendParts = parse_url($frontendUrl);
        $returnToParts = parse_url($returnTo);

        if (! is_array($frontendParts) || ! is_array($returnToParts)) {
            return null;
        }

        if (
            ! isset($frontendParts['scheme'], $frontendParts['host'], $returnToParts['scheme'], $returnToParts['host'])
            || strtolower((string) $frontendParts['scheme']) !== strtolower((string) $returnToParts['scheme'])
            || strtolower((string) $frontendParts['host']) !== strtolower((string) $returnToParts['host'])
            || ((int) ($frontendParts['port'] ?? 0)) !== ((int) ($returnToParts['port'] ?? 0))
        ) {
            return null;
        }

        $path = $returnToParts['path'] ?? '/';
        $query = isset($returnToParts['query']) ? '?' . $returnToParts['query'] : '';
        $fragment = isset($returnToParts['fragment']) ? '#' . $returnToParts['fragment'] : '';

        return $path . $query . $fragment;
    }

    public static function toFrontendUrl(?string $returnTo, string $defaultPath = '/auth/callback'): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');
        $path = self::normalize($returnTo) ?? $defaultPath;

        return $frontendUrl . (str_starts_with($path, '/') ? $path : '/' . $path);
    }
}
