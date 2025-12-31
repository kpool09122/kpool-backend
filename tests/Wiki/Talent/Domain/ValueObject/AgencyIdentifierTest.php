<?php

declare(strict_types=1);

namespace Tests\Wiki\Talent\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Talent\Domain\ValueObject\AgencyIdentifier;
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
        $id = StrTestHelper::generateUuid();
        $agencyIdentifier = new AgencyIdentifier($id);
        $this->assertSame($id, (string)$agencyIdentifier);
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
        new AgencyIdentifier($id);
    }
}
