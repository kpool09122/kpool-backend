<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ThumbnailUrl;

class ThumbnailUrlTest extends TestCase
{
    public function testAcceptsHttpAndHttpsAtMaximumLength(): void
    {
        foreach (['http', 'https'] as $scheme) {
            $prefix = $scheme . '://example.com/';
            $url = $prefix . str_repeat('a', 255 - strlen($prefix));
            self::assertSame($url, (string) new ThumbnailUrl($url));
        }
    }

    /** @return array<string, array{string}> */
    public static function invalidValues(): array
    {
        return [
            'empty' => [''],
            'relative URL' => ['/thumbnail.jpg'],
            'invalid URL' => ['https://'],
            'unsupported scheme' => ['ftp://example.com/thumb.jpg'],
            'too long' => ['https://example.com/' . str_repeat('a', 237)],
        ];
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidUrl(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ThumbnailUrl($value);
    }
}
