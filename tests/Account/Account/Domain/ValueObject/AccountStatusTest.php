<?php

declare(strict_types=1);

namespace Tests\Account\Account\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Domain\ValueObject\AccountStatus;

class AccountStatusTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'ACTIVE' => 'active',
            'PENDING' => 'pending',
            'SUSPENDED' => 'suspended',
        ], array_column(AccountStatus::cases(), 'value', 'name'));
    }
}
