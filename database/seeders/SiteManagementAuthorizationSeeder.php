<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;

class SiteManagementAuthorizationSeeder extends Seeder
{
    public const string GENERAL_GROUP = '69200000-0000-7000-8000-000000000001';
    public const string ADMIN_GROUP = '69200000-0000-7000-8000-000000000002';

    public function run(): void
    {
        foreach ([self::GENERAL_GROUP => 'general', self::ADMIN_GROUP => 'administrator'] as $id => $name) {
            $admin = $id === self::ADMIN_GROUP;
            $statements = $admin ? [['effect' => 'allow', 'actions' => array_map(static fn (Action $a): string => $a->value, Action::cases()), 'resource_types' => ['announcement','contact'], 'condition' => null]] : [['effect' => 'allow','actions' => ['contact:view'],'resource_types' => ['contact'],'condition' => 'own_contact']];
            DB::table('site_management_policies')->updateOrInsert(['id' => $id], ['name' => $name,'statements' => json_encode($statements, JSON_THROW_ON_ERROR),'created_at' => now(),'updated_at' => now()]);
            DB::table('site_management_roles')->updateOrInsert(['id' => $id], ['name' => $name,'created_at' => now(),'updated_at' => now()]);
            DB::table('site_management_principal_groups')->updateOrInsert(['id' => $id], ['name' => $name,'created_at' => now(),'updated_at' => now()]);
            DB::table('site_management_role_policy_attachments')->updateOrInsert(['role_id' => $id,'policy_id' => $id], []);
            DB::table('site_management_principal_group_role_attachments')->updateOrInsert(['principal_group_id' => $id,'role_id' => $id], []);
        }
    }
}
