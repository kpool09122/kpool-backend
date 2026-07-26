<?php

declare(strict_types=1);

namespace Tests\Account\Principal\Application\UseCase\Query;

use PHPUnit\Framework\TestCase;
use Source\Account\Principal\Application\UseCase\Query\MemberPrincipalGroupReadModel;
use Source\Account\Principal\Application\UseCase\Query\MemberReadModel;
use Tests\Helper\StrTestHelper;

class MemberReadModelTest extends TestCase
{
    public function test__construct(): void
    {
        $principalIdentifier = StrTestHelper::generateUuid();
        $identityIdentifier = StrTestHelper::generateUuid();
        $principalGroup = new MemberPrincipalGroupReadModel(
            principalGroupIdentifier: StrTestHelper::generateUuid(),
            name: 'Administrators',
            isDefault: true,
        );

        $readModel = new MemberReadModel(
            principalIdentifier: $principalIdentifier,
            identityIdentifier: $identityIdentifier,
            identityName: 'Test User',
            email: 'test@example.com',
            principalGroups: [$principalGroup],
        );

        $this->assertSame($principalIdentifier, $readModel->principalIdentifier());
        $this->assertSame($identityIdentifier, $readModel->identityIdentifier());
        $this->assertSame('Test User', $readModel->identityName());
        $this->assertSame('test@example.com', $readModel->email());
        $this->assertSame([$principalGroup], $readModel->principalGroups());
    }

    public function testToArray(): void
    {
        $principalIdentifier = StrTestHelper::generateUuid();
        $identityIdentifier = StrTestHelper::generateUuid();
        $principalGroupIdentifier = StrTestHelper::generateUuid();

        $readModel = new MemberReadModel(
            principalIdentifier: $principalIdentifier,
            identityIdentifier: $identityIdentifier,
            identityName: 'Test User',
            email: 'test@example.com',
            principalGroups: [
                new MemberPrincipalGroupReadModel(
                    principalGroupIdentifier: $principalGroupIdentifier,
                    name: 'Administrators',
                    isDefault: true,
                ),
            ],
        );

        $this->assertSame([
            'principalIdentifier' => $principalIdentifier,
            'identityIdentifier' => $identityIdentifier,
            'identityName' => 'Test User',
            'email' => 'test@example.com',
            'principalGroups' => [
                [
                    'principalGroupIdentifier' => $principalGroupIdentifier,
                    'name' => 'Administrators',
                    'isDefault' => true,
                ],
            ],
        ], $readModel->toArray());
    }
}
