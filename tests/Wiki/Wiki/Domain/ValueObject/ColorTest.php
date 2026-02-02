<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Color;

class ColorTest extends TestCase
{
    /**
     * 正常系: 6桁のHEXカラーコードでインスタンスが生成されること
     */
    public function test__constructWithSixDigitHex(): void
    {
        $color = new Color('#FF5733');
        $this->assertSame('#FF5733', (string) $color);
    }

    /**
     * 正常系: 3桁のHEXカラーコードでインスタンスが生成されること
     */
    public function test__constructWithThreeDigitHex(): void
    {
        $color = new Color('#F00');
        $this->assertSame('#F00', (string) $color);
    }

    /**
     * 正常系: 小文字のHEXカラーコードでインスタンスが生成されること
     */
    public function test__constructWithLowercaseHex(): void
    {
        $color = new Color('#ff5733');
        $this->assertSame('#ff5733', (string) $color);
    }

    /**
     * 正常系: 大文字小文字混在のHEXカラーコードでインスタンスが生成されること
     */
    public function test__constructWithMixedCaseHex(): void
    {
        $color = new Color('#Ff5733');
        $this->assertSame('#Ff5733', (string) $color);
    }

    /**
     * 異常系: 空文字で例外がスローされること
     */
    public function testThrowsInvalidArgumentExceptionWithEmptyColor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Color cannot be empty.');
        new Color('');
    }

    /**
     * 異常系: 不正なカラーコードで例外がスローされること
     */
    #[DataProvider('invalidColorProvider')]
    public function testThrowsInvalidArgumentExceptionWithInvalidColor(string $invalidColor): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Color must be a valid HEX color code (e.g., #FF5733 or #F00).');
        new Color($invalidColor);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function invalidColorProvider(): array
    {
        return [
            '色名' => ['red'],
            '#なし' => ['FF5733'],
            '4桁' => ['#FF57'],
            '5桁' => ['#FF573'],
            '7桁' => ['#FF57331'],
            '不正な文字を含む' => ['#GG5733'],
            'RGB形式' => ['rgb(255, 87, 51)'],
            'スペースを含む' => ['#FF 5733'],
        ];
    }
}
