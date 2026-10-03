<?php

declare(strict_types=1);

namespace Tests\Account\Shared\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Account\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\StrTestHelper;

class PrincipalIdentifierTest extends TestCase
{
    /**
     * 正常系: 有効なUUIDでインスタンスが生成されること
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();
        $policyIdentifier = new PrincipalIdentifier($id);
        $this->assertSame($id, (string) $policyIdentifier);
    }

    /**
     * 異常系: 不正な値の場合、例外が発生すること
     */
    public function testValidate(): void
    {
        $id = 'invalid-id';
        $this->expectException(InvalidArgumentException::class);
        new PrincipalIdentifier($id);
    }
}
