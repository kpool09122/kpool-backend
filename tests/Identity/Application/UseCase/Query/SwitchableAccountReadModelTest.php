<?php

declare(strict_types=1);

namespace Tests\Identity\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Identity\Application\UseCase\Query\AuthenticatedAccountReferenceReadModel;
use Source\Identity\Application\UseCase\Query\SwitchableAccountReadModel;

class SwitchableAccountReadModelTest extends TestCase
{
    public function testToArrayReturnsSwitchableAccount(): void
    {
        $readModel = new SwitchableAccountReadModel(
            delegationIdentifier: '019de7f3-78f3-7b55-9ed5-17f63e14d5dd',
            accountIdentifier: '019de7f3-78f3-7b55-9ed5-17f63e14d5ee',
            account: new AuthenticatedAccountReferenceReadModel(
                accountIdentifier: '019de7f3-78f3-7b55-9ed5-17f63e14d5ee',
                name: 'Delegator Account',
            ),
            isCurrent: true,
        );

        $this->assertSame([
            'delegationIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14d5dd',
            'accountIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14d5ee',
            'account' => [
                'accountIdentifier' => '019de7f3-78f3-7b55-9ed5-17f63e14d5ee',
                'name' => 'Delegator Account',
            ],
            'isCurrent' => true,
        ], $readModel->toArray());
    }

    public function testToArrayReturnsNotCurrentSwitchableAccount(): void
    {
        $readModel = new SwitchableAccountReadModel(
            delegationIdentifier: '019de7f3-78f3-7b55-9ed5-17f63e14d5dd',
            accountIdentifier: '019de7f3-78f3-7b55-9ed5-17f63e14d5ee',
            account: new AuthenticatedAccountReferenceReadModel(
                accountIdentifier: '019de7f3-78f3-7b55-9ed5-17f63e14d5ee',
                name: 'Delegator Account',
            ),
            isCurrent: false,
        );

        $payload = $readModel->toArray();

        $this->assertFalse($payload['isCurrent']);
    }
}
