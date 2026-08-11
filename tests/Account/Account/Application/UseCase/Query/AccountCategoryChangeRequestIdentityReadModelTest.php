<?php

declare(strict_types=1);

namespace Tests\Account\Account\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestIdentityReadModel;

class AccountCategoryChangeRequestIdentityReadModelTest extends TestCase
{
    public function testToArrayReturnsIdentityFields(): void
    {
        $readModel = new AccountCategoryChangeRequestIdentityReadModel(
            name: 'Alice',
            email: 'alice@example.com',
        );

        $this->assertSame([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ], $readModel->toArray());
    }
}
