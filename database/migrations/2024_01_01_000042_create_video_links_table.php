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
        Schema::create('video_links', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('動画リンクID');
            $table->string('resource_type', 16)->comment('リソースタイプ (agency, group, song, talent)');
            $table->uuid('resource_identifier')->comment('リソースID');
            $table->string('url', 512)->comment('動画URL');
            $table->string('video_usage', 32)->comment('動画用途');
            $table->string('title', 255)->comment('タイトル');
            $table->integer('display_order')->default(0)->comment('表示順');
            $table->timestamp('created_at')->comment('作成日時');

            $table->index(['resource_type', 'resource_identifier'], 'idx_video_links_resource');
            $table->unique(['resource_type', 'resource_identifier', 'url'], 'uniq_video_links_resource_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_links');
    }
};
