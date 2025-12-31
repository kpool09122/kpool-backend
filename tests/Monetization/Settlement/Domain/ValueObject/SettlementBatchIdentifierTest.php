<?php

declare(strict_types=1);

namespace Tests\Monetization\Settlement\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementBatchIdentifier;
use Tests\Helper\StrTestHelper;

class SettlementBatchIdentifierTest extends TestCase
{
    /**
     * 正常系: 正しくインスタンス化できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $id = StrTestHelper::generateUuid();

        $identifier = new SettlementBatchIdentifier($id);

        $this->assertSame($id, (string)$identifier);
    }

    /**
     * 異常系: 値が不適切な場合、例外が発生すること
     *
     * @return void
     */
    public function testValidate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SettlementBatchIdentifier('invalid');
    }
}
