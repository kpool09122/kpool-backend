<?php

declare(strict_types=1);

namespace Tests\Wiki\Wiki\Domain\ValueObject\Basic\Shared;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Wiki\Domain\ValueObject\Basic\Shared\Emoji;

class EmojiTest extends TestCase
{
    /**
     * 正常系: 絵文字でインスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $emoji = new Emoji('🐰');
        $this->assertSame('🐰', $emoji->value());
    }

    /**
     * 正常系: 空文字でインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithEmptyString(): void
    {
        $emoji = new Emoji('');
        $this->assertSame('', $emoji->value());
    }

    /**
     * 正常系: 複数の絵文字でインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithMultipleEmojis(): void
    {
        $emoji = new Emoji('🐰💜');
        $this->assertSame('🐰💜', $emoji->value());
    }

    /**
     * 正常系: 合成絵文字でインスタンスが生成されること
     *
     * @return void
     */
    public function test__constructWithCombinedEmoji(): void
    {
        $emoji = new Emoji('👨‍👩‍👧‍👦');
        $this->assertSame('👨‍👩‍👧‍👦', $emoji->value());
    }

    /**
     * 異常系: 通常の文字で例外がスローされること
     *
     * @return void
     */
    public function testThrowsInvalidArgumentExceptionWhenNotEmoji(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Emoji must contain only valid Unicode emoji characters.');
        new Emoji('abc');
    }

    /**
     * 異常系: 絵文字と通常の文字が混在する場合、例外がスローされること
     *
     * @return void
     */
    public function testThrowsInvalidArgumentExceptionWhenMixed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Emoji must contain only valid Unicode emoji characters.');
        new Emoji('🐰abc');
    }

    /**
     * 異常系: 17文字以上で例外がスローされること
     *
     * @return void
     */
    public function testThrowsInvalidArgumentExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Emoji must be 16 characters or less.');
        new Emoji(str_repeat('🐰', 17));
    }
}
