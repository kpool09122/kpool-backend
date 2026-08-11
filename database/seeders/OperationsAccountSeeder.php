<?php

declare(strict_types=1);

namespace Database\Seeders;

use Application\Http\Context\AuthContextCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class OperationsAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $operationsRoleId = DB::table('account_roles')->where('name', Role::OPERATIONS)->value('id');
        if (! is_string($operationsRoleId)) {
            throw new RuntimeException('Operations account role not found. Please run AccountAuthorizationSeeder first.');
        }

        $administratorRoleId = DB::table('roles')->where('name', 'ADMINISTRATOR')->value('id');
        if (! is_string($administratorRoleId)) {
            throw new RuntimeException('ADMINISTRATOR role not found. Please run SystemRoleSeeder first.');
        }

        $now = now();
        $accountId = '01965bb2-bcc9-7c6f-8b90-89f7f217fb01';
        $identityId = '01965bb2-bcc9-7c6f-8b90-89f7f217fb02';
        $principalId = '01965bb2-bcc9-7c6f-8b90-89f7f217fb03';
        $accountPrincipalGroupId = '01965bb2-bcc9-7c6f-8b90-89f7f217fb04';
        $accountMembershipId = '01965bb2-bcc9-7c6f-8b90-89f7f217fb05';
        $wikiDefaultPrincipalGroupId = '01965bb2-bcc9-7c6f-8b90-89f7f217fb06';
        $wikiPrincipalGroupId = '01965bb2-bcc9-7c6f-8b90-89f7f217fb07';

        DB::table('accounts')->upsert([[
            'id' => $accountId,
            'email' => 'operations@example.com',
            'type' => 'corporation',
            'name' => 'Operations Account',
            'status' => 'active',
            'category' => 'general',
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['id']);

        DB::table('identities')->upsert([[
            'id' => $identityId,
            'identity_name' => 'operations',
            'email' => 'operations@example.com',
            'language' => 'ja',
            'profile_image' => null,
            'password' => Hash::make('password'),
            'email_verified_at' => $now,
            'delegation_identifier' => null,
            'original_identity_identifier' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['id']);

        DB::table('account_principals')->upsert([[
            'id' => $principalId,
            'identity_id' => $identityId,
            'account_id' => $accountId,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['id']);

        DB::table('account_principal_groups')->upsert([[
            'id' => $accountPrincipalGroupId,
            'account_id' => $accountId,
            'name' => 'Operations',
            'is_default' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['id']);

        DB::table('account_principal_group_memberships')->upsert([[
            'id' => $accountMembershipId,
            'principal_group_id' => $accountPrincipalGroupId,
            'principal_id' => $principalId,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['principal_group_id', 'principal_id']);

        DB::table('account_principal_group_role_attachments')->upsert([[
            'principal_group_id' => $accountPrincipalGroupId,
            'role_id' => $operationsRoleId,
        ]], ['principal_group_id', 'role_id']);

        DB::table('wiki_principals')->upsert([[
            'id' => $principalId,
            'identity_id' => $identityId,
            'agency_id' => null,
            'group_ids' => json_encode([], JSON_THROW_ON_ERROR),
            'talent_ids' => json_encode([], JSON_THROW_ON_ERROR),
            'delegation_identifier' => null,
            'enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['id']);

        DB::table('principal_groups')->upsert([
            [
                'id' => $wikiDefaultPrincipalGroupId,
                'account_id' => $accountId,
                'name' => 'Default',
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $wikiPrincipalGroupId,
                'account_id' => $accountId,
                'name' => 'Operations Wiki Administrators',
                'is_default' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('principal_group_memberships')->upsert([[
            'principal_group_id' => $wikiPrincipalGroupId,
            'principal_id' => $principalId,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['principal_group_id', 'principal_id']);

        DB::table('principal_group_role_attachments')->upsert([[
            'principal_group_id' => $wikiPrincipalGroupId,
            'role_id' => $administratorRoleId,
        ]], ['principal_group_id', 'role_id']);

        $authContextCache = app(AuthContextCache::class);
        $identityIdentifier = new IdentityIdentifier($identityId);
        $authContextCache->forgetAccount($identityIdentifier);
        $authContextCache->forgetWiki($identityIdentifier);
    }
}
