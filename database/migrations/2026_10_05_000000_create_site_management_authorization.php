<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_management_principals', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('identity_id');
            $table->uuid('account_id');
            $table->timestamps();
            $table->unique(['identity_id', 'account_id']);
            $table->foreign('identity_id')->references('id')->on('identities')->cascadeOnDelete();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });

        Schema::create('site_management_principal_groups', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['account_id', 'name']);
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });

        Schema::create('site_management_roles', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id')->nullable()->index();
            $table->string('name');
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX site_management_roles_system_name_unique ON site_management_roles (name) WHERE account_id IS NULL');
        DB::statement('CREATE UNIQUE INDEX site_management_roles_account_name_unique ON site_management_roles (account_id, name) WHERE account_id IS NOT NULL');

        Schema::create('site_management_policies', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id')->nullable()->index();
            $table->string('name');
            $table->json('statements');
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX site_management_policies_system_name_unique ON site_management_policies (name) WHERE account_id IS NULL');
        DB::statement('CREATE UNIQUE INDEX site_management_policies_account_name_unique ON site_management_policies (account_id, name) WHERE account_id IS NOT NULL');

        Schema::create('site_management_principal_group_memberships', static function (Blueprint $table): void {
            $table->uuid('principal_group_id');
            $table->uuid('principal_id');
            $table->primary(['principal_group_id', 'principal_id']);
            $table->timestamps();
            $table->foreign('principal_group_id')->references('id')->on('site_management_principal_groups')->cascadeOnDelete();
            $table->foreign('principal_id')->references('id')->on('site_management_principals')->cascadeOnDelete();
        });

        Schema::create('site_management_principal_group_role_attachments', static function (Blueprint $table): void {
            $table->uuid('principal_group_id');
            $table->uuid('role_id');
            $table->primary(['principal_group_id', 'role_id']);
            $table->foreign('principal_group_id')->references('id')->on('site_management_principal_groups')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('site_management_roles')->cascadeOnDelete();
        });

        Schema::create('site_management_role_policy_attachments', static function (Blueprint $table): void {
            $table->uuid('role_id');
            $table->uuid('policy_id');
            $table->primary(['role_id', 'policy_id']);
            $table->foreign('role_id')->references('id')->on('site_management_roles')->cascadeOnDelete();
            $table->foreign('policy_id')->references('id')->on('site_management_policies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_management_principal_group_memberships');
        Schema::dropIfExists('site_management_principal_group_role_attachments');
        Schema::dropIfExists('site_management_role_policy_attachments');
        Schema::dropIfExists('site_management_principals');
        Schema::dropIfExists('site_management_principal_groups');
        Schema::dropIfExists('site_management_roles');
        Schema::dropIfExists('site_management_policies');
    }
};
