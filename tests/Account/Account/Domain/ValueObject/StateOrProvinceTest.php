<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\StateOrProvince;
use Tests\Helper\StrTestHelper;

class StateOrProvinceTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $value = 'Tokyo';
        $state = new StateOrProvince($value);
        $this->assertSame($value, (string)$state);
    }

    /**
     * 異常系: 空文字の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StateOrProvince('');
    }

    /**
     * 異常系: 空白のみの場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenOnlySpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StateOrProvince('   ');
    }

    /**
     * 異常系: 最大文字数を超える場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StateOrProvince(StrTestHelper::generateStr(StateOrProvince::MAX_LENGTH + 1));
    }
}
