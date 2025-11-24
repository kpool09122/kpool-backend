<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\Phone;

class PhoneTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成されること.
     */
    public function test__construct(): void
    {
        $raw = '+81 80-1234-5678';
        $phone = new Phone($raw);
        $this->assertSame('+818012345678', (string)$phone);
    }

    /**
     * 異常系: 空文字の場合、例外がスローされること.
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Phone('');
    }

    /**
     * 異常系: 空白のみの場合、例外がスローされること.
     */
    public function testWhenOnlySpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Phone('   ');
    }

    /**
     * 異常系: 不正な文字を含む場合、例外がスローされること.
     */
    public function testWhenContainsInvalidCharacters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Phone('+81-ABC-1234');
    }

    /**
     * 異常系: 桁数が足りない場合、例外がスローされること.
     */
    public function testWhenTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Phone('+123456');
    }

    /**
     * 異常系: 最大桁数を超える場合、例外がスローされること.
     */
    public function testWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Phone('+' . str_repeat('1', Phone::MAX_DIGITS + 1));
    }
}
