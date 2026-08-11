<?php

declare(strict_types=1);

namespace Database\Seeders;

use Application\Http\Context\AuthContextCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Source\Account\Principal\Domain\Entity\Role;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

class TestAccountSeeder extends Seeder
{
    private const array ACCOUNTS = [
        [
            'accountId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa01',
            'identityId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa02',
            'principalId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa03',
            'accountDefaultPrincipalGroupId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa0d',
            'principalGroupId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa04',
            'accountOwnerPrincipalGroupMembershipId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa05',
            'email' => 'test@example.com',
            'identityName' => 'test-account',
            'accountName' => 'Test Account',
            'accountType' => 'individual',
        ],
        [
            'accountId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa06',
            'identityId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa07',
            'principalId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa08',
            'accountDefaultPrincipalGroupId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa0f',
            'principalGroupId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa09',
            'accountOwnerPrincipalGroupMembershipId' => '01965bb2-bcc9-7c6f-8b90-89f7f217fa0a',
            'email' => 'corp@example.com',
            'identityName' => 'corp-test-account',
            'accountName' => 'Corporate Test Account',
            'accountType' => 'corporation',
        ],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $ownerRoleId = DB::table('account_roles')
            ->where('name', Role::OWNER)
            ->value('id');

        if (! is_string($ownerRoleId)) {
            throw new RuntimeException('Owner account role not found. Please run AccountAuthorizationSeeder first.');
        }

        $now = now();

        foreach (self::ACCOUNTS as $account) {
            $this->createTestAccount($account, $ownerRoleId, $now);
        }
    }

    /**
     * @param array{
     *     accountId: string,
     *     identityId: string,
     *     principalId: string,
     *     accountDefaultPrincipalGroupId: string,
     *     principalGroupId: string,
     *     accountOwnerPrincipalGroupMembershipId: string,
     *     email: string,
     *     identityName: string,
     *     accountName: string,
     *     accountType: string
     * } $account
     */
    private function createTestAccount(
        array $account,
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
                'id' => $account['accountDefaultPrincipalGroupId'],
                'account_id' => $account['accountId'],
                'name' => 'Default',
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => $account['principalGroupId'],
                'account_id' => $account['accountId'],
                'name' => 'Owners',
                'is_default' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('account_principal_group_memberships')
            ->where('principal_group_id', $account['accountDefaultPrincipalGroupId'])
            ->where('principal_id', $account['principalId'])
            ->delete();

        DB::table('account_principal_group_memberships')->upsert([
            [
                'id' => $account['accountOwnerPrincipalGroupMembershipId'],
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

        $identityIdentifier = new IdentityIdentifier($account['identityId']);
        $authContextCache = app(AuthContextCache::class);
        $authContextCache->forgetAccount($identityIdentifier);
        $authContextCache->forgetWiki($identityIdentifier);
    }
}
