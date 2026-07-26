<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Source\Account\Principal\Domain\Entity\Role;

class FullAccessTestAccountSeeder extends Seeder
{
    private const array ACCOUNTS = [
        [
            'accountId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa01',
            'identityId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa02',
            'principalId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa03',
            'principalGroupId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa04',
            'wikiDefaultPrincipalGroupId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa0b',
            'accountPrincipalGroupMembershipId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa05',
            'email' => 'test@example.com',
            'identityName' => 'full-access-test',
            'accountName' => 'Full Access Test Account',
            'principalGroupName' => 'Full Access Test Group',
            'accountType' => 'individual',
        ],
        [
            'accountId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa06',
            'identityId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa07',
            'principalId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa08',
            'principalGroupId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa09',
            'wikiDefaultPrincipalGroupId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa0c',
            'accountPrincipalGroupMembershipId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa0a',
            'email' => 'corp@example.com',
            'identityName' => 'corp-full-access-test',
            'accountName' => 'Corporate Full Access Test Account',
            'principalGroupName' => 'Corporate Full Access Test Group',
            'accountType' => 'corporation',
        ],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $administratorRoleId = DB::table('roles')
            ->where('name', 'ADMINISTRATOR')
            ->value('id');

        if (! is_string($administratorRoleId)) {
            throw new RuntimeException('ADMINISTRATOR role not found. Please run SystemRoleSeeder first.');
        }

        $collaboratorRoleId = DB::table('roles')
            ->where('name', 'COLLABORATOR')
            ->value('id');

        if (! is_string($collaboratorRoleId)) {
            throw new RuntimeException('COLLABORATOR role not found. Please run SystemRoleSeeder first.');
        }

        $ownerRoleId = DB::table('account_roles')
            ->where('name', Role::OWNER)
            ->value('id');

        if (! is_string($ownerRoleId)) {
            throw new RuntimeException('Owner account role not found. Please run AccountAuthorizationSeeder first.');
        }

        $now = now();

        foreach (self::ACCOUNTS as $account) {
            $this->createFullAccessAccount($account, $administratorRoleId, $collaboratorRoleId, $ownerRoleId, $now);
        }
    }

    /**
     * @param array{
     *     accountId: string,
     *     identityId: string,
     *     principalId: string,
     *     principalGroupId: string,
     *     wikiDefaultPrincipalGroupId: string,
     *     accountPrincipalGroupMembershipId: string,
     *     email: string,
     *     identityName: string,
     *     accountName: string,
     *     principalGroupName: string,
     *     accountType: string
     * } $account
     */
    private function createFullAccessAccount(
        array $account,
        string $administratorRoleId,
        string $collaboratorRoleId,
        string $ownerRoleId,
        Carbon $now,
    ): void {
        DB::table('accounts')->upsert([
            [
                'id' => $account['accountId'],
                'email' => $account['email'],
                'type' => $account['accountType'],
                'name' => $account['accountName'],
                'status' => 'active',
                'category' => 'general',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('identities')->upsert([
            [
                'id' => $account['identityId'],
                'identity_name' => $account['identityName'],
                'email' => $account['email'],
                'language' => 'ja',
                'profile_image' => null,
                'password' => Hash::make('password'),
                'email_verified_at' => $now,
                'delegation_identifier' => null,
                'original_identity_identifier' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('wiki_principals')->upsert([
            [
                'id' => $account['principalId'],
                'identity_id' => $account['identityId'],
                'agency_id' => null,
                'group_ids' => json_encode([], JSON_THROW_ON_ERROR),
                'talent_ids' => json_encode([], JSON_THROW_ON_ERROR),
                'delegation_identifier' => null,
                'enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('account_principals')->upsert([
            [
                'id' => $account['principalId'],
                'identity_id' => $account['identityId'],
                'account_id' => $account['accountId'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('account_principal_groups')->upsert([
            [
                'id' => $account['principalGroupId'],
                'account_id' => $account['accountId'],
                'name' => $account['principalGroupName'],
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('account_principal_group_memberships')->upsert([
            [
                'id' => $account['accountPrincipalGroupMembershipId'],
                'principal_group_id' => $account['principalGroupId'],
                'principal_id' => $account['principalId'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['principal_group_id', 'principal_id']);

        DB::table('account_principal_group_role_attachments')->upsert([
            [
                'principal_group_id' => $account['principalGroupId'],
                'role_id' => $ownerRoleId,
            ],
        ], ['principal_group_id', 'role_id']);

        DB::table('principal_groups')->upsert([
            [
                'id' => $account['wikiDefaultPrincipalGroupId'],
                'account_id' => $account['accountId'],
                'name' => 'Default',
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $account['principalGroupId'],
                'account_id' => $account['accountId'],
                'name' => $account['principalGroupName'],
                'is_default' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('principal_group_memberships')->upsert([
            [
                'principal_group_id' => $account['wikiDefaultPrincipalGroupId'],
                'principal_id' => $account['principalId'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'principal_group_id' => $account['principalGroupId'],
                'principal_id' => $account['principalId'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['principal_group_id', 'principal_id']);

        DB::table('principal_group_role_attachments')->upsert([
            [
                'principal_group_id' => $account['wikiDefaultPrincipalGroupId'],
                'role_id' => $collaboratorRoleId,
            ],
            [
                'principal_group_id' => $account['principalGroupId'],
                'role_id' => $administratorRoleId,
            ],
        ], ['principal_group_id', 'role_id']);
    }
}
