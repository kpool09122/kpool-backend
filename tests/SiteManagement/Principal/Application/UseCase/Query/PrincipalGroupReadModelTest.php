<?php

declare(strict_types=1);

namespace Tests\SiteManagement\Principal\Application\UseCase\Query;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Source\SiteManagement\Principal\Application\UseCase\Query\PrincipalGroupMemberReadModel;
use Source\SiteManagement\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use Tests\Helper\StrTestHelper;

class PrincipalGroupReadModelTest extends TestCase
{
    /** @return array<string, array{string|null}> */
    public static function descriptions(): array
    {
        return ['with description' => ['Manages site management user groups and their memberships.'], 'without description' => [null]];
    }

    #[DataProvider('descriptions')]
    public function testToArray(?string $description): void
    {
        $principalGroupIdentifier = StrTestHelper::generateUuid();
        $accountIdentifier = StrTestHelper::generateUuid();
        $roleIdentifier = StrTestHelper::generateUuid();
        $principalIdentifier = StrTestHelper::generateUuid();
        $identityIdentifier = StrTestHelper::generateUuid();
        $members = [new PrincipalGroupMemberReadModel($principalIdentifier, $identityIdentifier, 'alice', 'alice@example.com')];

        $readModel = new PrincipalGroupReadModel(
            principalGroupIdentifier: $principalGroupIdentifier,
            accountIdentifier: $accountIdentifier,
            name: 'Administrators',
            description: $description,
            roleIdentifiers: [$roleIdentifier],
            isDefault: false,
            members: $members,
        );

        $this->assertSame($principalGroupIdentifier, $readModel->principalGroupIdentifier());
        $this->assertSame($accountIdentifier, $readModel->accountIdentifier());
        $this->assertSame('Administrators', $readModel->name());
        $this->assertSame($description, $readModel->description());
        $this->assertSame([$roleIdentifier], $readModel->roleIdentifiers());
        $this->assertFalse($readModel->isDefault());
        $this->assertSame($members, $readModel->members());
        $this->assertSame([
            'principalGroupIdentifier' => $principalGroupIdentifier,
            'accountIdentifier' => $accountIdentifier,
            'name' => 'Administrators',
            'description' => $description,
            'roleIdentifiers' => [$roleIdentifier],
            'isDefault' => false,
            'members' => [[
                'principalIdentifier' => $principalIdentifier,
                'identityIdentifier' => $identityIdentifier,
                'identityName' => 'alice',
                'email' => 'alice@example.com',
            ]],
        ], $readModel->toArray());
    }
}
