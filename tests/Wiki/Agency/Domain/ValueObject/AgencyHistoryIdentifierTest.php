<?php

declare(strict_types=1);

namespace Tests\Wiki\Agency\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Wiki\Agency\Domain\ValueObject\AgencyHistoryIdentifier;
use Tests\Helper\StrTestHelper;

class AgencyHistoryIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();
        $historyIdentifier = new AgencyHistoryIdentifier($ulid);
        $this->assertSame($ulid, (string)$historyIdentifier);
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
        new AgencyHistoryIdentifier($ulid);
    }
}
