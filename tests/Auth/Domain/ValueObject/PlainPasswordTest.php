<?php

declare(strict_types=1);

namespace Tests\Auth\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Auth\Domain\ValueObject\PlainPassword;

class PlainPasswordTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $password = 'Abcdef12!';

        $plainPassword = new PlainPassword($password);

        $this->assertSame($password, (string)$plainPassword);
    }

    /**
     * 正常系: 8文字以上の場合、正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithMinLengthBoundary(): void
    {
        $password = 'Abcdef1!'; // 8 chars

        $plainPassword = new PlainPassword($password);

        $this->assertSame($password, (string)$plainPassword);
    }

    /**
     * 正常系: 20文字以下の場合、正しくインスタンスを作成できること.
     *
     * @return void
     */
    public function testConstructWithMaxLengthBoundary(): void
    {
        $password = 'Abcdefghijklmnopqr12'; // 20 chars

        $plainPassword = new PlainPassword($password);

        $this->assertSame($password, (string)$plainPassword);
    }

    /**
     * 異常系: 8文字未満の場合、例外がスローされること.
     *
     * @return void
     */
    public function testConstructWithTooShortPasswordThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PlainPassword('short1!');
    }

    /**
     * 異常系: 20文字を超える場合、例外がスローされること.
     *
     * @return void
     */
    public function testConstructWithTooLongPasswordThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PlainPassword(str_repeat('a', PlainPassword::MAX_LENGTH + 1));
    }

    /**
     * 異常系: 使用できない文字が含まれている場合、例外がスローされること.
     *
     * @return void
     */
    public function testConstructWithInvalidCharactersThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PlainPassword('invalid password'); // space is not allowed
    }
}
