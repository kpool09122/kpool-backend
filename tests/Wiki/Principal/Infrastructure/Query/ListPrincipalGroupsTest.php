<?php

declare(strict_types=1);

namespace Tests\Wiki\Principal\Infrastructure\Query;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\Wiki\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInput;
use Source\Wiki\Principal\Application\UseCase\Query\ListPrincipalGroups\ListPrincipalGroupsInterface;
use Source\Wiki\Principal\Application\UseCase\Query\PrincipalGroupReadModel;
use Source\Wiki\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\Wiki\Principal\Domain\ValueObject\RoleIdentifier;
use Source\Wiki\Principal\Infrastructure\Query\ListPrincipalGroups;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Tests\Helper\CreateAccount;
use Tests\Helper\CreateIdentity;
use Tests\Helper\CreatePrincipal;
use Tests\Helper\CreatePrincipalGroup;
use Tests\Helper\CreatePrincipalGroupMembership;
use Tests\Helper\CreateRole;
use Tests\Helper\StrTestHelper;
use Tests\TestCase;

class ListPrincipalGroupsTest extends TestCase
{
    public function test__construct(): void
    {
        $this->assertInstanceOf(ListPrincipalGroups::class, $this->app->make(ListPrincipalGroupsInterface::class));
    }

    #[Group('useDb')]
    public function testProcessReturnsPrincipalGroupsForAccountInCreatedOrder(): void
    {
        $accountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        $otherAccountIdentifier = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string) $accountIdentifier);
        CreateAccount::create((string) $otherAccountIdentifier);

        $firstGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $secondGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        $otherGroupIdentifier = new PrincipalGroupIdentifier(StrTestHelper::generateUuid());
        CreatePrincipalGroup::create($firstGroupIdentifier, $accountIdentifier, ['name' => 'Admins', 'is_default' => true]);
        CreatePrincipalGroup::create($secondGroupIdentifier, $accountIdentifier, ['name' => 'Editors']);
        CreatePrincipalGroup::create($otherGroupIdentifier, $otherAccountIdentifier, ['name' => 'Other']);

        DB::table('wiki_principal_groups')->where('id', (string) $firstGroupIdentifier)->update(['created_at' => now()->subMinute()]);
        DB::table('wiki_principal_groups')->where('id', (string) $secondGroupIdentifier)->update(['created_at' => now()]);

        $roleIdentifier = new RoleIdentifier(StrTestHelper::generateUuid());
        CreateRole::create($roleIdentifier);
        DB::table('wiki_principal_group_role_attachments')->insert([
            'principal_group_id' => (string) $firstGroupIdentifier,
            'role_id' => (string) $roleIdentifier,
        ]);

        $principalIdentifier = new PrincipalIdentifier(StrTestHelper::generateUuid());
        $identityIdentifier = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identityIdentifier, ['identityName' => 'alice', 'email' => 'alice@example.com']);
        CreatePrincipal::create($principalIdentifier, $identityIdentifier);
        CreatePrincipalGroupMembership::create((string) $firstGroupIdentifier, (string) $principalIdentifier);

        $groups = (new ListPrincipalGroups())->process(new ListPrincipalGroupsInput($accountIdentifier));

        $this->assertCount(2, $groups);
        $this->assertInstanceOf(PrincipalGroupReadModel::class, $groups[0]);
        $this->assertSame((string) $firstGroupIdentifier, $groups[0]->principalGroupIdentifier());
        $this->assertSame((string) $secondGroupIdentifier, $groups[1]->principalGroupIdentifier());
        $this->assertSame([
            'principalGroupIdentifier' => (string) $firstGroupIdentifier,
            'accountIdentifier' => (string) $accountIdentifier,
            'name' => 'Admins',
            'roleIdentifiers' => [(string) $roleIdentifier],
            'isDefault' => true,
            'members' => [[
                'principalIdentifier' => (string) $principalIdentifier,
                'identityIdentifier' => (string) $identityIdentifier,
                'identityName' => 'alice',
                'email' => 'alice@example.com',
            ]],
        ], $groups[0]->toArray());
    }
}
