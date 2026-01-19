<?php

declare(strict_types=1);

namespace Tests\Account\Invitation\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Invitation\Domain\ValueObject\InvitationToken;
use Tests\Helper\StrTestHelper;

/**
 * @covers \Source\Account\Invitation\Domain\ValueObject\InvitationToken
 */
class InvitationTokenTest extends TestCase
{
    /**
     * 64文字のhexadecimal文字列で正常に生成できることを確認する
     */
    public function test__construct(): void
    {
        $value = StrTestHelper::generateHex(64);
        $token = new InvitationToken($value);

        $this->assertSame($value, (string) $token);
    }

    /**
     * 64文字未満の場合に例外が発生することを確認する
     */
    public function testThrowsExceptionWhenTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('InvitationToken must be 64 characters.');

        new InvitationToken(StrTestHelper::generateHex(62));
    }

    /**
     * 64文字超の場合に例外が発生することを確認する
     */
    public function testThrowsExceptionWhenTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('InvitationToken must be 64 characters.');

        new InvitationToken(StrTestHelper::generateHex(66));
    }

    /**
     * hexadecimal以外の文字が含まれる場合に例外が発生することを確認する
     */
    public function testThrowsExceptionWhenNotHexadecimal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('InvitationToken must be a hexadecimal string.');

        new InvitationToken(str_repeat('g', 64));
    }
}
