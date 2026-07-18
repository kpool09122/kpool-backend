<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Color;
use Source\Wiki\Wiki\Domain\ValueObject\HexColor;

class ColorTest extends TestCase
{
    public function test__constructWithColorCodeAndLabel(): void
    {
        $color = new Color(new HexColor('#FF5733'), 'Apricot');

        $this->assertSame('#FF5733', (string) $color->colorCode());
        $this->assertSame('Apricot', $color->label());
        $this->assertSame('#FF5733', (string) $color);
        $this->assertSame(['color_code' => '#FF5733', 'label' => 'Apricot'], $color->toArray());
        $this->assertSame(['colorCode' => '#FF5733', 'label' => 'Apricot'], $color->toApiArray());
    }

    #[DataProvider('invalidLabelProvider')]
    public function testThrowsInvalidArgumentExceptionWithInvalidLabel(string $label, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new Color(new HexColor('#FF5733'), $label);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function invalidLabelProvider(): array
    {
        return [
            '空文字' => ['', 'Color label cannot be empty.'],
            'trim後空文字' => ['   ', 'Color label cannot be empty.'],
            '17文字' => ['12345678901234567', 'Color label must be 16 characters or less.'],
        ];
    }
}
