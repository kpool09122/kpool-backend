<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementAccountIdentifier;
use Tests\Helper\StrTestHelper;

class SettlementAccountIdentifierTest extends TestCase
{
    /**
     * 正常系: ULID が指定されていればインスタンス化できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();

        $identifier = new SettlementAccountIdentifier($ulid);

        $this->assertSame($ulid, (string)$identifier);
    }

    /**
     * 異常系: ULID 形式以外の場合は例外となること.
     *
     * @return void
     */
    public function testValidate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettlementAccountIdentifier('not-a-ulid');
    }
}
