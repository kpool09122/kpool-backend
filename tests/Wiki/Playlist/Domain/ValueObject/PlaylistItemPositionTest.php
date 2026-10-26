<?php

declare(strict_types=1);

namespace Tests\Wiki\Playlist\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Playlist\Domain\ValueObject\PlaylistItemPosition;

class PlaylistItemPositionTest extends TestCase
{
    /** @return array<string, array{int}> */
    public static function validPositions(): array
    {
        return ['first' => [0], 'later' => [49], 'large' => [PHP_INT_MAX]];
    }

    #[DataProvider('validPositions')]
    public function testAcceptsNonNegativePosition(int $value): void
    {
        self::assertSame($value, new PlaylistItemPosition($value)->value());
    }

    public function testRejectsNegativePosition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlaylistItemPosition(-1);
    }
}
