<?php

declare(strict_types=1);

namespace Source\Shared\Infrastructure\Support;

use Illuminate\Support\Facades\Storage;

final readonly class ImageUrl
{
    public static function fromPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $url = str_starts_with($path, '/')
            ? url($path)
            : Storage::disk((string) config('filesystems.image_disk', 'public'))->url($path);

        return self::normalizeLocalhost($url);
    }

    private static function normalizeLocalhost(string $url): string
    {
        return preg_replace('/^http:\/\/localhost(?=[:\/])/', 'http://127.0.0.1', $url) ?? $url;
    }
}
