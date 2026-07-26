<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('account_policies', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Account Policy ID');
            $table->string('name', 255)->unique()->comment('Account Policy名');
            $table->json('statements')->comment('Statement の配列（JSON）');
            $table->boolean('is_system_policy')->default(false)->comment('システムPolicyかどうか');
            $table->timestamps();
        });

        Schema::create('account_roles', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Account Role ID');
            $table->string('name', 255)->unique()->comment('Account Role名');
            $table->boolean('is_system_role')->default(false)->comment('システムRoleかどうか');
            $table->timestamps();
        });

        Schema::create('account_role_policy_attachments', static function (Blueprint $table) {
            $table->uuid('role_id')->comment('Account Role ID');
            $table->uuid('policy_id')->comment('Account Policy ID');

            $table->primary(['role_id', 'policy_id']);

            $table->foreign('role_id')
                ->references('id')
                ->on('account_roles')
                ->cascadeOnDelete();

            $table->foreign('policy_id')
                ->references('id')
                ->on('account_policies')
                ->cascadeOnDelete();
        });

        Schema::create('account_principal_group_role_attachments', static function (Blueprint $table) {
            $table->uuid('principal_group_id')->comment('PrincipalグループID');
            $table->uuid('role_id')->comment('Account Role ID');

            $table->primary(['principal_group_id', 'role_id']);

            $table->foreign('principal_group_id')
                ->references('id')
                ->on('account_principal_groups')
                ->cascadeOnDelete();

            $table->foreign('role_id')
                ->references('id')
                ->on('account_roles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_principal_group_role_attachments');
        Schema::dropIfExists('account_role_policy_attachments');
        Schema::dropIfExists('account_roles');
        Schema::dropIfExists('account_policies');
    }
};
