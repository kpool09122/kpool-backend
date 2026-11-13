<?php

declare(strict_types=1);

namespace Tests\Wiki\Image\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Image\Domain\ValueObject\ImageAuthor;

class ImageAuthorTest extends TestCase
{
    public function testTrimsAndAcceptsUpTo255Characters(): void
    {
        self::assertSame('作者', (string) new ImageAuthor("　作者 \n"));
        self::assertSame(str_repeat('作', 255), (string) new ImageAuthor(str_repeat('作', 255)));
    }

    /** @return array<string, array{string}> */
    public static function invalidAuthors(): array
    {
        return [
            'empty' => [''],
            'blank' => ["　 \t"],
            'too long' => [str_repeat('作', 256)],
        ];
    }

    #[DataProvider('invalidAuthors')]
    public function testRejectsInvalidAuthors(string $author): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ImageAuthor($author);
    }
}
