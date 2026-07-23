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

        Schema::create('account_role_policy_attachments', static function (Blueprint $table) {
            $table->string('role', 20)->comment('Account role');
            $table->uuid('policy_id')->comment('Account Policy ID');

            $table->primary(['role', 'policy_id']);

            $table->foreign('policy_id')
                ->references('id')
                ->on('account_policies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_role_policy_attachments');
        Schema::dropIfExists('account_policies');
    }
};
