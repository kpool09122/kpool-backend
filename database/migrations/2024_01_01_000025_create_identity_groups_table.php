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
        Schema::create('identity_groups', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('IdentityグループID');
            $table->uuid('account_id')->index()->comment('アカウントID');
            $table->string('name', 100)->comment('グループ名');
            $table->string('role', 20)->comment('ロール（owner, admin, member）');
            $table->boolean('is_default')->default(false)->comment('デフォルトグループかどうか');
            $table->timestamps();

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->cascadeOnDelete();
        });

        Schema::create('identity_group_memberships', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('メンバーシップID');
            $table->uuid('identity_group_id')->index()->comment('IdentityグループID');
            $table->uuid('identity_id')->index()->comment('IdentityID');
            $table->timestamps();

            $table->foreign('identity_group_id')
                ->references('id')
                ->on('identity_groups')
                ->cascadeOnDelete();

            $table->foreign('identity_id')
                ->references('id')
                ->on('identities')
                ->cascadeOnDelete();

            $table->unique(['identity_group_id', 'identity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_group_memberships');
        Schema::dropIfExists('identity_groups');
    }
};
