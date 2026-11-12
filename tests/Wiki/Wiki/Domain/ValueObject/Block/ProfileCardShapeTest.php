<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Block;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Block\ProfileCardShape;

class ProfileCardShapeTest extends TestCase
{
    #[DataProvider('shapeValues')]
    public function testFromValue(mixed $value, ProfileCardShape $expected): void
    {
        $this->assertSame($expected, ProfileCardShape::fromValue($value));
    }

    /**
     * @return iterable<string, array{mixed, ProfileCardShape}>
     */
    public static function shapeValues(): iterable
    {
        yield 'portrait' => ['portrait', ProfileCardShape::PORTRAIT];
        yield 'square' => ['square', ProfileCardShape::SQUARE];
        yield 'landscape' => ['landscape', ProfileCardShape::LANDSCAPE];
        yield 'missing value' => [null, ProfileCardShape::PORTRAIT];
        yield 'unknown shape' => ['wide', ProfileCardShape::PORTRAIT];
        yield 'non-string value' => [42, ProfileCardShape::PORTRAIT];
    }
}
