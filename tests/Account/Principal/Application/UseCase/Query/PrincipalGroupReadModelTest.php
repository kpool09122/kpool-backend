<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Source\Account\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use Tests\Helper\StrTestHelper;

class PrincipalGroupReadModelTest extends TestCase
{
    public function test__construct(): void
    {
        $principalGroupIdentifier = StrTestHelper::generateUuid();
        $accountIdentifier = StrTestHelper::generateUuid();
        $roleIdentifiers = [
            StrTestHelper::generateUuid(),
            StrTestHelper::generateUuid(),
        ];
        $members = [
            new PrincipalGroupMemberReadModel(
                principalIdentifier: StrTestHelper::generateUuid(),
                identityIdentifier: StrTestHelper::generateUuid(),
                identityName: 'alice',
                email: 'alice@example.com',
            ),
            new PrincipalGroupMemberReadModel(
                principalIdentifier: StrTestHelper::generateUuid(),
                identityIdentifier: StrTestHelper::generateUuid(),
                identityName: 'bob',
                email: 'bob@example.com',
            ),
        ];

        $readModel = new PrincipalGroupReadModel(
            principalGroupIdentifier: $principalGroupIdentifier,
            accountIdentifier: $accountIdentifier,
            name: 'Administrators',
            roleIdentifiers: $roleIdentifiers,
            isDefault: true,
            members: $members,
        );

        $this->assertSame($principalGroupIdentifier, $readModel->principalGroupIdentifier());
        $this->assertSame($accountIdentifier, $readModel->accountIdentifier());
        $this->assertSame('Administrators', $readModel->name());
        $this->assertSame($roleIdentifiers, $readModel->roleIdentifiers());
        $this->assertTrue($readModel->isDefault());
        $this->assertSame($members, $readModel->members());
    }

    public function testToArray(): void
    {
        $principalGroupIdentifier = StrTestHelper::generateUuid();
        $accountIdentifier = StrTestHelper::generateUuid();
        $roleIdentifiers = [
            StrTestHelper::generateUuid(),
            StrTestHelper::generateUuid(),
        ];
        $principalIdentifier = StrTestHelper::generateUuid();
        $identityIdentifier = StrTestHelper::generateUuid();
        $members = [
            new PrincipalGroupMemberReadModel(
                principalIdentifier: $principalIdentifier,
                identityIdentifier: $identityIdentifier,
                identityName: 'alice',
                email: 'alice@example.com',
            ),
        ];

        $readModel = new PrincipalGroupReadModel(
            principalGroupIdentifier: $principalGroupIdentifier,
            accountIdentifier: $accountIdentifier,
            name: 'Administrators',
            roleIdentifiers: $roleIdentifiers,
            isDefault: false,
            members: $members,
        );

        $this->assertSame([
            'principalGroupIdentifier' => $principalGroupIdentifier,
            'accountIdentifier' => $accountIdentifier,
            'name' => 'Administrators',
            'roleIdentifiers' => $roleIdentifiers,
            'isDefault' => false,
            'members' => [
                [
                    'principalIdentifier' => $principalIdentifier,
                    'identityIdentifier' => $identityIdentifier,
                    'identityName' => 'alice',
                    'email' => 'alice@example.com',
                ],
            ],
        ], $readModel->toArray());
    }
}
