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
     * 正常系: ULID から識別子を生成できること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();

        $identifier = new SettlementBatchIdentifier($ulid);

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

        new SettlementBatchIdentifier('invalid');
    }
}
