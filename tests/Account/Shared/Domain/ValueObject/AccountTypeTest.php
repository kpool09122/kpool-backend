<?php

declare(strict_types=1);

namespace Tests\Account\Shared\Domain\ValueObject;

use PHPUnit\Framework\TestCase;
use Source\Account\Shared\Domain\ValueObject\AccountType;

class AccountTypeTest extends TestCase
{
    public function testPersistedValues(): void
    {
        $this->assertSame([
            'CORPORATION' => 'corporation',
            'INDIVIDUAL' => 'individual',
        ], array_column(AccountType::cases(), 'value', 'name'));
    }
}
