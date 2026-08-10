<?php

declare(strict_types=1);

namespace Source\Account\Account\Domain\ValueObject;

enum AccountDocumentFileType: string
{
    case PDF = 'application/pdf';
    case JPEG = 'image/jpeg';
    case PNG = 'image/png';
    case WEBP = 'image/webp';
    case HEIC = 'image/heic';
    case HEIF = 'image/heif';

    public static function tryFromMimeType(string $mimeType): ?self
    {
        return self::tryFrom($mimeType);
    }

    public function extension(): string
    {
        return match ($this) {
            self::PDF => 'pdf',
            self::JPEG => 'jpg',
            self::PNG => 'png',
            self::WEBP => 'webp',
            self::HEIC => 'heic',
            self::HEIF => 'heif',
        };
    }
}
