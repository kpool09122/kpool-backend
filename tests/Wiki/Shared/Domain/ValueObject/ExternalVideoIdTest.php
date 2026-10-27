<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\ExternalVideoId;

class ExternalVideoIdTest extends TestCase
{
    public function testAcceptsCaseSensitiveVideoId(): void
    {
        self::assertSame('aB_12345-xy', (string) new ExternalVideoId('aB_12345-xy'));
    }

    /** @return array<string, array{string, bool, bool}> */
    public static function providerIds(): array
    {
        return [
            'YouTube alphabet' => ['aB_12345-xy', true, false],
            'YouTube numeric' => ['00000000000', true, false],
            'YouTube too short' => ['abcdefghij', false, false],
            'YouTube too long' => ['abcdefghijkl', false, false],
            'TikTok minimum' => ['100000000000000', false, true],
            'TikTok maximum' => ['12345678901234567890', false, true],
            'TikTok too short' => ['12345678901234', false, false],
            'TikTok too long' => ['123456789012345678901', false, false],
            'TikTok leading zero' => ['012345678901234', false, false],
            'TikTok nonnumeric' => ['12345678901234a', false, false],
            'trailing newline' => ["123456789012345\n", false, false],
            'whitespace' => [' 123456789012345 ', false, false],
            'empty' => ['', false, false],
        ];
    }

    #[DataProvider('providerIds')]
    public function testValidatesProviderFormatsAndAcceptsEitherFormat(string $value, bool $youtube, bool $tiktok): void
    {
        self::assertSame($youtube, ExternalVideoId::isValidYouTubeId($value));
        self::assertSame($tiktok, ExternalVideoId::isValidTikTokId($value));
        if (! $youtube && ! $tiktok) {
            $this->expectException(InvalidArgumentException::class);
        }
        self::assertSame($value, (string) new ExternalVideoId($value));
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
