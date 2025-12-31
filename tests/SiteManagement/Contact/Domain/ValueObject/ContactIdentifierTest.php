<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactIdentifier;
use Tests\Helper\StrTestHelper;

class ContactIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $contactIdentifier = new ContactIdentifier($id);
        $this->assertSame($id, (string)$contactIdentifier);
    }

    /**
     * 異常系: 値が不適切な場合、例外が発生すること
     *
     * @return void
     */
    public function testValidate(): void
    {
        $id = 'invalid-id';
        $this->expectException(InvalidArgumentException::class);
        new ContactIdentifier($id);
    }
}
