<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\AffiliationGrantIdentifier;
use Tests\Helper\StrTestHelper;

class AffiliationGrantIdentifierTest extends TestCase
{
    /**
     * 正常系: 有効なUUIDでインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $affiliationGrantIdentifier = new AffiliationGrantIdentifier($id);
        $this->assertSame($id, (string) $affiliationGrantIdentifier);
    }

    /**
     * 異常系: 不正な値の場合、例外が発生すること
     */
    public function testValidate(): void
    {
        $id = 'invalid-id';
        $this->expectException(InvalidArgumentException::class);
        new AffiliationGrantIdentifier($id);
    }
}
