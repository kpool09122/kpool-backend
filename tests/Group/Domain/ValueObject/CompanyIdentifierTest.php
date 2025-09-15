<?php

namespace Tests\Group\Domain\ValueObject;

use Businesses\Group\Domain\ValueObject\CompanyIdentifier;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class CompanyIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();
        $companyIdentifier = new CompanyIdentifier($ulid);
        $this->assertSame($ulid, (string)$companyIdentifier);
    }

    /**
     * 異常系: ulidが不適切な場合、例外が発生すること
     *
     * @return void
     */
    public function testValidate(): void
    {
        $ulid = 'invalid-ulid';
        $this->expectException(InvalidArgumentException::class);
        new CompanyIdentifier($ulid);
    }
}
