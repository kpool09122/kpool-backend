<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Domain\ValueObject\ExternalVideoId;

class ExternalVideoIdTest extends TestCase
{
    public function testAcceptsCaseSensitiveVideoId(): void
    {
        self::assertSame('aB_12345-xy', (string) new ExternalVideoId('aB_12345-xy'));
    }

    /** @return array<string, array{string}> */
    public static function invalidIds(): array
    {
        return ['empty' => [''], 'short' => ['123'], 'long' => ['123456789012'], 'character' => ['1234567890!'], 'whitespace' => [' 12345678901 ']];
    }

    #[DataProvider('invalidIds')]
    public function testRejectsInvalidId(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ExternalVideoId($value);
    }
}
