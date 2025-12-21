<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Agency\Domain\ValueObject\AgencyName;
use Tests\Helper\StrTestHelper;

class AgencyNameTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $name = 'JYP엔터테인먼트';
        $agencyName = new AgencyName($name);
        $this->assertSame($name, (string)$agencyName);
    }

    /**
     * 異常系：空文字が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AgencyName('');
    }

    /**
     * 異常系：空白が渡された場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenOnlySpace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AgencyName('    ');
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AgencyName(StrTestHelper::generateStr(AgencyName::MAX_LENGTH + 1));
    }
}
