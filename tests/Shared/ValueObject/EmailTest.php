<?php

declare(strict_types=1);

namespace Tests\Shared\ValueObject;

use Businesses\Shared\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class EmailTest extends TestCase
{
    /**
     * 正常系: インスタンスが正しく生成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $email = 'test@example.local';
        $emailObj = new Email($email);
        $this->assertSame($email, (string)$emailObj);
    }

    /**
     * 異常系: 不正な形式のメールアドレスの場合、例外をスローすること.
     *
     * @return void
     */
    public function testIsEmail_1(): void
    {
        $expected = 'a';
        $this->expectException(InvalidArgumentException::class);
        new Email($expected);
    }

    /**
     * 異常系: 不正な形式のメールアドレスの場合、例外をスローすること.
     *
     * @return void
     */
    public function testIsEmail_2(): void
    {
        $expected = 'a@a';
        $this->expectException(InvalidArgumentException::class);
        new Email($expected);
    }

    /**
     * 正常系: 正しい形式のメールアドレスの場合、正しく値を保持していること.
     *
     * @return void
     */
    public function testIsEmail_3(): void
    {
        $expected = 'a@a.c';
        $Email = new Email($expected);
        $this->assertSame($expected, (string)$Email);
    }

    /**
     * 異常系: メールアドレスが空の文字列の場合、例外をスローすること.
     *
     * @return void
     */
    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Email('');
    }

    /**
     * 正常系:メールアドレスのローカルパートの最大文字数を許容できること.
     * ローカルパート(@の前):64文字.
     */
    public function testCheckMaxLengthOfLocalPart(): void
    {
        $localPart = StrTestHelper::generateStr(64);
        $domainPart = 'test.jp';
        $expected = $localPart . '@' . $domainPart;
        $inquiryAccountEmail = new Email($expected);
        $this->assertSame($expected, (string)$inquiryAccountEmail);
    }

    /**
     * 異常系:メールアドレスのローカルパートの最大文字数を超過する場合、例外をスローすること.
     * ローカルパート(@の前):64文字.
     */
    public function testCheckMaxLengthOfLocalPart_2(): void
    {
        $localPart = StrTestHelper::generateStr(65);
        $domainPart = 'test.jp';
        $expected = $localPart . '@' . $domainPart;
        $this->expectException(InvalidArgumentException::class);
        new Email($expected);
    }

    /**
     * 正常系:メールアドレスのドメインパートの最大文字数を許容できること.
     * なお、ドメインパートの'.'と'.'の間は63文字以内とする必要がある.
     * ドメインパート(@の後)の最大文字数は254文字までだが、そもそもメールアドレス全体の文字数が254文字以内（RFC 5321のセクション4.5.3.1.3のパス規定参照）となっていないとエラーになるため、
     * ドメインパートの最大文字数は実質的に252文字以内までとなる。
     */
    public function testCheckMaxLengthOfDomainPart(): void
    {
        $localPart = StrTestHelper::generateStr(1);
        $domainPart = StrTestHelper::generateStr(63) . '.'
            . StrTestHelper::generateStr(63) . '.'
            . StrTestHelper::generateStr(63) . '.'
            . StrTestHelper::generateStr(57) . '.jp';
        $expected = $localPart . '@' . $domainPart;
        $inquiryAccountEmail = new Email($expected);
        $this->assertSame($expected, (string)$inquiryAccountEmail);
    }

    /**
     * 異常系:メールアドレスのドメインパートの最大文字数を超える場合、例外をスローすること.
     *
     * @return void
     */
    public function testCheckMaxLengthOfDomainPart_2(): void
    {
        $localPart = StrTestHelper::generateStr(1);
        $domainPart = StrTestHelper::generateStr(63) . '.'
            . StrTestHelper::generateStr(63) . '.'
            . StrTestHelper::generateStr(63) . '.'
            . StrTestHelper::generateStr(58) . '.jp';
        $expected = $localPart . '@' . $domainPart;
        $this->expectException(InvalidArgumentException::class);
        new Email($expected);
    }

    /**
     * 異常系: メールアドレスの文字数が最大文字数より大きい場合に例外をスローすること.
     * RFC 5321のセクション4.5.3.1.3では、フォワードパス（forward-path）またはリバースパス（reverse-path）の最大長が256オクテットであると規定
     * この「パス」は、メールアドレスを山括弧< >で囲んだ形式（例: <user@example.com>）として定義
     * よって、メールアドレス自体の最大長は、パスの最大長である256オクテットから、山括弧2つ分の2オクテットを引いた254オクテット.
     *
     * @return void
     */
    public function testMaxLength(): void
    {
        $localPart = StrTestHelper::generateStr(64);
        $domainPart = StrTestHelper::generateStr(63) . '.'
            . StrTestHelper::generateStr(63) . '.'
            . StrTestHelper::generateStr(59) . '.jp';
        $expected = $localPart . '@' . $domainPart;
        $this->expectException(InvalidArgumentException::class);
        new Email($expected);
    }
}
