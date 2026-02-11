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
        Schema::create('video_link_collection_statuses', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('収集状態ID');
            $table->string('resource_type', 16)->comment('リソースタイプ (group, song, talent)');
            $table->uuid('wiki_id')->comment('Wiki ID');
            $table->timestamp('last_collected_at')->nullable()->comment('最終収集日時');
            $table->timestamp('created_at')->comment('作成日時');

            $table->unique(['resource_type', 'wiki_id'], 'uniq_vlcs_resource');
            $table->index(['last_collected_at'], 'idx_vlcs_last_collected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_link_collection_statuses');
    }
};
