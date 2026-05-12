<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class FullAccessTestAccountSeeder extends Seeder
{
    private const string ACCOUNT_ID = '01965bb2-bcc9-7c6f-8b90-89f7f217fa01';
    private const string IDENTITY_ID = '01965bb2-bcc9-7c6f-8b90-89f7f217fa02';
    private const string PRINCIPAL_ID = '01965bb2-bcc9-7c6f-8b90-89f7f217fa03';
    private const string PRINCIPAL_GROUP_ID = '01965bb2-bcc9-7c6f-8b90-89f7f217fa04';
    private const string EMAIL = 'test@example.com';

    public function run(): void
    {
        $administratorRoleId = DB::table('roles')
            ->where('name', 'ADMINISTRATOR')
            ->value('id');

        if (! is_string($administratorRoleId)) {
            throw new RuntimeException('ADMINISTRATOR role not found. Please run SystemRoleSeeder first.');
        }

        $now = now();

        DB::table('accounts')->upsert([
            [
                'id' => self::ACCOUNT_ID,
                'email' => self::EMAIL,
                'type' => 'individual',
                'name' => 'Full Access Test Account',
                'status' => 'active',
                'category' => 'general',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('identities')->upsert([
            [
                'id' => self::IDENTITY_ID,
                'username' => 'full-access-test',
                'email' => self::EMAIL,
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
                'id' => self::PRINCIPAL_ID,
                'identity_id' => self::IDENTITY_ID,
                'agency_id' => null,
                'group_ids' => json_encode([], JSON_THROW_ON_ERROR),
                'talent_ids' => json_encode([], JSON_THROW_ON_ERROR),
                'delegation_identifier' => null,
                'enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('principal_groups')->upsert([
            [
                'id' => self::PRINCIPAL_GROUP_ID,
                'account_id' => self::ACCOUNT_ID,
                'name' => 'Full Access Test Group',
                'is_default' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['id']);

        DB::table('principal_group_memberships')->upsert([
            [
                'principal_group_id' => self::PRINCIPAL_GROUP_ID,
                'principal_id' => self::PRINCIPAL_ID,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['principal_group_id', 'principal_id']);

        DB::table('principal_group_role_attachments')->upsert([
            [
                'principal_group_id' => self::PRINCIPAL_GROUP_ID,
                'role_id' => $administratorRoleId,
            ],
        ], ['principal_group_id', 'role_id']);
    }
}
