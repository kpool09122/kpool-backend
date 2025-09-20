<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Domain\ValueObject;

use Businesses\SiteManagement\Contact\Domain\ValueObject\ContactName;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class ContactNameTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $text = '新機能の追加に関するお願い';
        $contactName = new ContactName($text);
        $this->assertSame($text, (string)$contactName);
    }

    /**
     * 異常系：空文字の場合、例外がスローされること.
     *
     * @return void
     */
    public function testWhenEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ContactName('');
    }

    /**
     * 異常系：最大文字数を超えた場合、例外がスローされること.
     *
     * @return void
     */
    public function testExceedMaxChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ContactName(StrTestHelper::generateStr(ContactName::MAX_LENGTH + 1));
    }
}
