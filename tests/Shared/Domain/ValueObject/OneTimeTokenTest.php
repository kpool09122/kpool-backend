<?php

declare(strict_types=1);

namespace Tests\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Shared\Domain\ValueObject\OneTimeToken;
use Tests\Helper\StrTestHelper;

/**
 * @covers \Source\Shared\Domain\ValueObject\OneTimeToken
 */
class OneTimeTokenTest extends TestCase
{
    /**
     * 64文字のhexadecimal文字列で正常に生成できることを確認する
     */
    public function test__construct(): void
    {
        $value = StrTestHelper::generateHex(64);
        $token = new OneTimeToken($value);

        $this->assertSame($value, (string) $token);
    }

    /**
     * 64文字未満の場合に例外が発生することを確認する
     */
    public function testThrowsExceptionWhenTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OneTimeToken must be 64 characters.');

        new OneTimeToken(StrTestHelper::generateHex(62));
    }

    /**
     * 64文字超の場合に例外が発生することを確認する
     */
    public function testThrowsExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OneTimeToken must be 64 characters.');

        new OneTimeToken(StrTestHelper::generateHex(66));
    }

    /**
     * hexadecimal以外の文字が含まれる場合に例外が発生することを確認する
     */
    public function testThrowsExceptionWhenNotHexadecimal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('OneTimeToken must be a hexadecimal string.');

        new OneTimeToken(str_repeat('g', 64));
    }
}
