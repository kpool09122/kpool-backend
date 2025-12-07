<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;
use Tests\Helper\StrTestHelper;

class SettlementScheduleIdentifierTest extends TestCase
{
    /**
     * 正常系: ULID から識別子を生成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();

        $identifier = new SettlementScheduleIdentifier($ulid);

        $this->assertSame($ulid, (string)$identifier);
    }

    /**
     * 異常系: ULID 形式以外では例外となること.
     *
     * @return void
     */
    public function testValidate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettlementScheduleIdentifier('invalid');
    }
}
