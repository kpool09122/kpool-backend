<?php

namespace Tests\Wiki\Agency\Domain\ValueObject;

use Businesses\Wiki\Group\Domain\ValueObject\AgencyIdentifier;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Helper\StrTestHelper;

class AgencyIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();
        $agencyIdentifier = new AgencyIdentifier($ulid);
        $this->assertSame($ulid, (string)$agencyIdentifier);
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
        new AgencyIdentifier($ulid);
    }
}
