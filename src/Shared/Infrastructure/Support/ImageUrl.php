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

        if (str_starts_with($path, '/')) {
            return url($path);
        }

        return Storage::disk((string) config('filesystems.image_disk', 'public'))->url($path);
    }
}
