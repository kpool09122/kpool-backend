<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delegation_permissions', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('委譲権限ID');
            $table->uuid('principal_group_id')->index()->comment('PrincipalグループID');
            $table->uuid('target_account_id')->index()->comment('対象アカウントID');
            $table->uuid('affiliation_id')->index()->comment('所属ID');
            $table->timestamps();

            $table->foreign('principal_group_id')
                ->references('id')
                ->on('account_principal_groups')
                ->cascadeOnDelete();

            $table->foreign('target_account_id')
                ->references('id')
                ->on('accounts')
                ->cascadeOnDelete();

            $table->unique(['principal_group_id', 'target_account_id', 'affiliation_id'], 'delegation_permissions_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delegation_permissions');
    }
};
