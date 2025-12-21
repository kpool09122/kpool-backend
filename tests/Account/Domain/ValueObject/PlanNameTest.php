<?php

declare(strict_types=1);

namespace Tests\Account\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Domain\ValueObject\PlanName;
use Tests\Helper\StrTestHelper;

class PlanNameTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = 'test-name';
        $accountName = new PlanName($name);
        $this->assertSame($name, (string)$accountName);
    }

    /**
     * 異常系：空文字が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlanName('');
    }

    /**
     * 異常系：空白が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenOnlySpace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlanName('    ');
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlanName(StrTestHelper::generateStr(PlanName::MAX_LENGTH + 1));
    }
}
