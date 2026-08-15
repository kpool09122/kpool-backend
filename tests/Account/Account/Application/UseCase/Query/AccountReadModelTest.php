<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\AccountReadModel;
use Source\Account\Account\Domain\ValueObject\AccountStatus;
use Source\Account\Shared\Domain\ValueObject\AccountCategory;
use Source\Account\Shared\Domain\ValueObject\AccountType;
use Tests\Helper\StrTestHelper;

class AccountReadModelTest extends TestCase
{
    public function testToArrayReturnsAccountSummary(): void
    {
        $accountIdentifier = StrTestHelper::generateUuid();
        $readModel = new AccountReadModel(
            accountIdentifier: $accountIdentifier,
            email: 'test@example.com',
            type: AccountType::CORPORATION->value,
            name: 'Example Inc',
            status: AccountStatus::ACTIVE->value,
            accountCategory: AccountCategory::GENERAL->value,
        );

        $this->assertSame([
            'accountIdentifier' => $accountIdentifier,
            'email' => 'test@example.com',
            'type' => AccountType::CORPORATION->value,
            'name' => 'Example Inc',
            'status' => AccountStatus::ACTIVE->value,
            'accountCategory' => AccountCategory::GENERAL->value,
            'phone' => null,
            'address' => null,
        ], $readModel->toArray());
    }
}
