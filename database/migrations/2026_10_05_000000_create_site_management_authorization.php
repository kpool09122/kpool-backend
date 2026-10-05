<?php

declare(strict_types=1);
use Database\Seeders\SiteManagementAuthorizationSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    // Original SiteManagement User Role::ADMIN backed value.
    private const string LEGACY_ADMIN_ROLE = 'admin';

    public function up(): void
    {
        Schema::create('site_management_principals', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('identity_id')->unique();
            $table->timestamps();
            $table->foreign('identity_id')->references('id')->on('identities')->cascadeOnDelete();
        });
        foreach (['principal_groups','roles','policies'] as $name) {
            Schema::create('site_management_'.$name, static function (Blueprint $table) use ($name) {
                $table->uuid('id')->primary();
                $table->string('name')->unique();
                if ($name === 'policies') {
                    $table->json('statements');
                } $table->timestamps();
            });
        }
        foreach ([['principal_group_memberships','principal_group_id','principal_groups','principal_id','principals'],['principal_group_role_attachments','principal_group_id','principal_groups','role_id','roles'],['role_policy_attachments','role_id','roles','policy_id','policies']] as [$name,$left,$leftTable,$right,$rightTable]) {
            Schema::create('site_management_'.$name, static function (Blueprint $table) use ($left, $leftTable, $right, $rightTable) {
                $table->uuid($left);
                $table->uuid($right);
                $table->primary([$left,$right]);
                $table->foreign($left)->references('id')->on('site_management_'.$leftTable)->cascadeOnDelete();
                $table->foreign($right)->references('id')->on('site_management_'.$rightTable)->cascadeOnDelete();
            });
        }
        (new SiteManagementAuthorizationSeeder())->run();
        // Copy identifiers and bindings; legacy callers and Identity-based history remain intact.
        DB::table('site_management_users')->orderBy('id')->chunk(200, static function ($users) {
            foreach ($users as $user) {
                DB::table('site_management_principals')->insert(['id' => $user->id,'identity_id' => $user->identity_id,'created_at' => $user->created_at,'updated_at' => $user->updated_at]);
                DB::table('site_management_principal_group_memberships')->insert(['principal_id' => $user->id,'principal_group_id' => $user->role === self::LEGACY_ADMIN_ROLE ? SiteManagementAuthorizationSeeder::ADMIN_GROUP : SiteManagementAuthorizationSeeder::GENERAL_GROUP]);
            }
        });
        Schema::drop('site_management_users');
    }

    public function down(): void
    {
        $legacyMigration = require __DIR__.'/2024_01_01_000004_create_site_management_users_table.php';
        $legacyMigration->up();
        DB::table('site_management_principals')->orderBy('id')->each(static function (object $principal): void {
            $isAdmin = DB::table('site_management_principal_group_memberships')
                ->where('principal_id', $principal->id)
                ->where('principal_group_id', SiteManagementAuthorizationSeeder::ADMIN_GROUP)->exists();
            DB::table('site_management_users')->insert([
                'id' => $principal->id, 'identity_id' => $principal->identity_id,
                'role' => $isAdmin ? self::LEGACY_ADMIN_ROLE : 'none',
                'created_at' => $principal->created_at, 'updated_at' => $principal->updated_at,
            ]);
        });
        foreach (['principal_group_memberships' ,'principal_group_role_attachments','role_policy_attachments','principals','principal_groups','roles','policies'] as $name) {
            Schema::dropIfExists('site_management_'.$name);
        }
    }
};
