<?php

declare(strict_types=1);

namespace Tests\Monetization\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Monetization\Account\Domain\ValueObject\PayoutAccountStatus;

class PayoutAccountStatusTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'ACTIVE' => 'active',
            'INACTIVE' => 'inactive',
        ], array_column(PayoutAccountStatus::cases(), 'value', 'name'));
    }
}
