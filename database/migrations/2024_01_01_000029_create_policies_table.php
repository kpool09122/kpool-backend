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
        Schema::create('policies', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Policy ID');
            $table->string('name', 255)->comment('ポリシー名');
            $table->json('statements')->comment('Statement の配列（JSON）');
            $table->boolean('is_system_policy')->default(false)->comment('システムポリシーかどうか（削除不可）');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
