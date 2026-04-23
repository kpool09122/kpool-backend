<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Contact\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Contact\Domain\ValueObject\ContactReplyIdentifier;
use Tests\Helper\StrTestHelper;

class ContactReplyIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $identifier = new ContactReplyIdentifier($id);
        $this->assertSame($id, (string)$identifier);
    }

    /**
     * 異常系: 値が不適切な場合、例外が発生すること
     */
    public function testValidate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ContactReplyIdentifier('invalid-id');
    }
}
