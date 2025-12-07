<?php

declare(strict_types=1);

namespace Tests\Monetization\Billing\Domain\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Source\Monetization\Billing\Domain\ValueObject\BillingCycleIdentifier;
use Tests\Helper\StrTestHelper;

class BillingCycleIdentifierTest extends TestCase
{
    /**
     * 正常系: インスタンスが生成されること.
     *
     * @return void
     */
    public function test__construct(): void
    {
        $ulid = StrTestHelper::generateUlid();

        $identifier = new BillingCycleIdentifier($ulid);

        $this->assertSame($ulid, (string)$identifier);
    }

    /**
     * 異常系: 不正な値は例外となること.
     *
     * @return void
     */
    public function testValidate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BillingCycleIdentifier('not-a-ulid');
    }
}
