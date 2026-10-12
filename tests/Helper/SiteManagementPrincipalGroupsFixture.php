<?php

declare(strict_types=1);

namespace Tests\Helper;

use Database\Seeders\SiteManagementAuthorizationSeeder;
use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\Principal\Domain\Entity\Principal;
use Source\SiteManagement\Principal\Domain\Entity\PrincipalGroup;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalGroupIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\RoleIdentifier;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalGroupRepository;
use Source\SiteManagement\Principal\Infrastructure\Repository\PrincipalRepository;

class SiteManagementPrincipalGroupsFixture
{
    public AccountIdentifier $account;
    public AccountIdentifier $otherAccount;
    public Principal $administrator;
    public Principal $otherAdministrator;
    public Principal $unassigned;
    public Principal $foreign;
    public PrincipalGroup $administrators;
    public PrincipalGroup $general;
    public PrincipalGroup $protected;

    public function __construct()
    {
        app(SiteManagementAuthorizationSeeder::class)->run();
        $this->account = new AccountIdentifier(StrTestHelper::generateUuid());
        $this->otherAccount = new AccountIdentifier(StrTestHelper::generateUuid());
        CreateAccount::create((string)$this->account);
        CreateAccount::create((string)$this->otherAccount);
        $this->administrator = $this->principal($this->account, 'Alice');
        $this->otherAdministrator = $this->principal($this->account, 'Bob');
        $this->unassigned = $this->principal($this->account, 'Unassigned');
        $this->foreign = $this->principal($this->otherAccount, 'Foreign');
        $this->administrators = $this->group($this->account, [SiteManagementAuthorizationSeeder::ADMINISTRATOR_ROLE], [$this->administrator,$this->otherAdministrator]);
        $this->general = $this->group($this->account, [SiteManagementAuthorizationSeeder::GENERAL_ROLE], []);
        $this->protected = $this->group($this->account, [SiteManagementAuthorizationSeeder::OPERATOR_ROLE,SiteManagementAuthorizationSeeder::ADMINISTRATOR_ROLE], [$this->administrator]);
    }

    public function principal(AccountIdentifier $account, string $name): Principal
    {
        $identity = new IdentityIdentifier(StrTestHelper::generateUuid());
        CreateIdentity::create($identity, ['identityName' => $name,'email' => (string)$identity.'@example.com']);
        $principal = new Principal(new PrincipalIdentifier(StrTestHelper::generateUuid()), $identity, $account);
        (new PrincipalRepository())->save($principal);
        DB::table('account_principals')->insert(['id' => StrTestHelper::generateUuid(),'account_id' => (string)$account,'identity_id' => (string)$identity]);

        return $principal;
    }

    /** @param string[] $roles
     * @param Principal[] $members */
    public function group(AccountIdentifier $account, array $roles, array $members): PrincipalGroup
    {
        $group = new PrincipalGroup(new PrincipalGroupIdentifier(StrTestHelper::generateUuid()), 'Display name '.StrTestHelper::generateUuid(), array_map(static fn (string $role): RoleIdentifier => new RoleIdentifier($role), $roles), $account);
        $group->replaceMembers(array_map(static fn (Principal $member): PrincipalIdentifier => $member->principalIdentifier(), $members));
        (new PrincipalGroupRepository())->save($group);

        return $group;
    }
}
