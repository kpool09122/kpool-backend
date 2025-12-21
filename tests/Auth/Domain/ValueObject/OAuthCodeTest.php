<?php

declare(strict_types=1);

namespace Tests\Auth\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Auth\Domain\ValueObject\OAuthCode;

class OAuthCodeTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスが作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $rawCode = 'code';
        $code = new OAuthCode($rawCode);

        $this->assertSame($rawCode, (string)$code);
    }

    /**
     * 異常系: 空の値の時、例外がスローされること.
     *
     * @return void
     */
    public function testThrowsExceptionWhenCodeIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OAuthCode('');
    }

    /**
     * 異常系: 空白の時、例外がスローされること.
     *
     * @return void
     */
    public function testThrowsExceptionWhenCodeIsOnlySpace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OAuthCode('    ');
    }
}
