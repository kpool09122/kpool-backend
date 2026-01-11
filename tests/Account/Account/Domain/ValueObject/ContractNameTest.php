<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\ContractName;
use Tests\Helper\StrTestHelper;

class ContractNameTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = 'Test Taro';
        $contactName = new ContractName($name);
        $this->assertSame($name, (string)$contactName);
    }

    /**
     * 異常系：空文字の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ContractName('');
    }

    /**
     * 異常系：スペースだけの場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenOnlySpace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ContractName('  ');
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ContractName(StrTestHelper::generateStr(ContractName::MAX_LENGTH + 1));
    }
}
