<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Principal\Domain\ValueObject\PolicyIdentifier;
use Tests\Helper\StrTestHelper;

class PolicyIdentifierTest extends TestCase
{
    /**
     * 正常系: 有効なUUIDでインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $policyIdentifier = new PolicyIdentifier($id);
        $this->assertSame($id, (string) $policyIdentifier);
    }

    /**
     * 異常系: 不正な値の場合、例外が発生すること
     */
    public function testValidate(): void
    {
        $id = 'invalid-id';
        $this->expectException(InvalidArgumentException::class);
        new PolicyIdentifier($id);
    }
}
