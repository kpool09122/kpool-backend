<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistName;

class PlaylistNameTest extends TestCase
{
    public function testTrimsAndAcceptsOneAndThirtyTwoCharacters(): void
    {
        self::assertSame('曲', (string) new PlaylistName('  曲  '));
        self::assertSame('曲', (string) new PlaylistName('　曲　'));
        self::assertSame(str_repeat('曲', 32), (string) new PlaylistName('  ' . str_repeat('曲', 32) . '  '));
    }

    /** @return array<string, array{string}> */
    public static function invalidNames(): array
    {
        return ['empty' => [''], 'blank' => [" \t\n"], 'unicode blank' => ['　'], 'too long' => [str_repeat('曲', 33)]];
    }

    #[DataProvider('invalidNames')]
    public function testRejectsInvalidNames(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlaylistName($name);
    }
}
