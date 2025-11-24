<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\PostalCode;
use Tests\Helper\StrTestHelper;

class PostalCodeTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $value = '100-0001';
        $postalCode = new PostalCode($value);
        $this->assertSame($value, (string)$postalCode);
    }

    /**
     * 異常系: 空文字の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PostalCode('');
    }

    /**
     * 異常系: 空白のみの場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenOnlySpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PostalCode('   ');
    }

    /**
     * 異常系: 最大文字数を超える場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PostalCode(StrTestHelper::generateStr(PostalCode::MAX_LENGTH + 1));
    }
}
