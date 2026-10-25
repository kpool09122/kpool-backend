<?php

declare(strict_types=1);

namespace Tests\Wiki\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Shared\Domain\ValueObject\VideoTitle;

class VideoTitleTest extends TestCase
{
    public function testAcceptsMultibyteTitleAtMaximumLength(): void
    {
        $title = str_repeat('動', 255);
        self::assertSame($title, (string) new VideoTitle($title));
    }

    /** @return array<string, array{string}> */
    public static function invalidValues(): array
    {
        return ['empty' => [''], 'blank' => ['   '], 'too long' => [str_repeat('動', 256)]];
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidTitle(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VideoTitle($value);
    }
}
