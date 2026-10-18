<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\HexColor;

class HexColorTest extends TestCase
{
    #[DataProvider('validHexColorProvider')]
    public function test__constructWithValidHexColor(string $value): void
    {
        $color = new HexColor($value);

        $this->assertSame($value, (string) $color);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validHexColorProvider(): array
    {
        return [
            '小文字' => ['#ff5733'],
            '数字のみ' => ['#123456'],
            '黒' => ['#000000'],
            '白' => ['#ffffff'],
        ];
    }

    public function testThrowsInvalidArgumentExceptionWithEmptyColor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hex color cannot be empty.');

        new HexColor('');
    }

    #[DataProvider('invalidHexColorProvider')]
    public function testThrowsInvalidArgumentExceptionWithInvalidColor(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hex color must be a lowercase #rrggbb color code (e.g., #ff5733).');

        new HexColor($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidHexColorProvider(): array
    {
        return [
            '大文字' => ['#FF5733'],
            '大文字小文字混在' => ['#Ff5733'],
            '前後の空白' => [' #ff5733 '],
            '末尾の改行' => ["#ff5733\n"],
            '色名' => ['red'],
            '#なし' => ['FF5733'],
            '3桁' => ['#F00'],
            '4桁' => ['#FF57'],
            '5桁' => ['#FF573'],
            '7桁' => ['#FF57331'],
            '不正な文字を含む' => ['#GG5733'],
            'RGB形式' => ['rgb(255, 87, 51)'],
            'スペースを含む' => ['#FF 5733'],
        ];
    }
}
