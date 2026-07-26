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
        Schema::create('account_principals', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Principal ID');
            $table->uuid('identity_id')->comment('Identity ID');
            $table->uuid('account_id')->index()->comment('アカウントID');
            $table->timestamps();

            $table->foreign('identity_id')
                ->references('id')
                ->on('identities')
                ->cascadeOnDelete();
            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->cascadeOnDelete();
            $table->unique(['identity_id', 'account_id']);
        });

        Schema::create('account_principal_groups', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('PrincipalグループID');
            $table->uuid('account_id')->index()->comment('アカウントID');
            $table->string('name', 100)->comment('グループ名');
            $table->boolean('is_default')->default(false)->comment('デフォルトグループかどうか');
            $table->timestamps();

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->cascadeOnDelete();
        });

        Schema::create('account_principal_group_memberships', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('メンバーシップID');
            $table->uuid('principal_group_id')->index()->comment('PrincipalグループID');
            $table->uuid('principal_id')->index()->comment('Principal ID');
            $table->timestamps();

            $table->foreign('principal_group_id')
                ->references('id')
                ->on('account_principal_groups')
                ->cascadeOnDelete();

            $table->foreign('principal_id')
                ->references('id')
                ->on('account_principals')
                ->cascadeOnDelete();

            $table->unique(['principal_group_id', 'principal_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_principal_group_memberships');
        Schema::dropIfExists('account_principal_groups');
        Schema::dropIfExists('account_principals');
    }
};
