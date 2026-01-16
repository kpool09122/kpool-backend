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
        Schema::create('wiki_images', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('画像ID');
            $table->string('resource_type', 16)->comment('リソースタイプ (talent, song, group)');
            $table->uuid('resource_identifier')->comment('リソースID');
            $table->string('image_path', 255)->comment('画像パス');
            $table->string('image_usage', 16)->comment('画像用途 (profile, cover, logo, additional)');
            $table->integer('display_order')->default(0)->comment('表示順');
            $table->string('source_url', 512)->comment('出典元URL');
            $table->string('source_name', 255)->comment('出典元サイト名');
            $table->string('alt_text', 512)->comment('alt属性テキスト');
            $table->timestamps();

            $table->index(['resource_type', 'resource_identifier'], 'idx_resource');
            $table->index('image_usage', 'idx_usage');
        });

        Schema::create('draft_wiki_images', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('画像ID');
            $table->uuid('published_id')->nullable()->comment('公開済み画像ID');
            $table->string('resource_type', 16)->comment('リソースタイプ (talent, song, group)');
            $table->uuid('draft_resource_identifier')->comment('下書きリソースID');
            $table->uuid('editor_id')->comment('編集者ID');
            $table->string('image_path', 255)->comment('画像パス');
            $table->string('image_usage', 16)->comment('画像用途 (profile, cover, logo, additional)');
            $table->integer('display_order')->default(0)->comment('表示順');
            $table->string('source_url', 512)->comment('出典元URL');
            $table->string('source_name', 255)->comment('出典元サイト名');
            $table->string('alt_text', 512)->comment('alt属性テキスト');
            $table->string('status', 16)->default('pending')->comment('承認ステータス (pending, under_review, approved, rejected)');
            $table->timestamp('agreed_to_terms_at')->comment('規約同意日時');
            $table->timestamps();

            $table->index(['resource_type', 'draft_resource_identifier'], 'idx_draft_resource');
        });

        Schema::create('wiki_image_snapshots', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('スナップショットID');
            $table->uuid('image_id')->comment('画像ID');
            $table->uuid('resource_snapshot_identifier')->comment('リソーススナップショットID');
            $table->string('image_path', 255)->comment('画像パス');
            $table->string('image_usage', 16)->comment('画像用途');
            $table->integer('display_order')->comment('表示順');
            $table->string('source_url', 512)->comment('出典元URL');
            $table->string('source_name', 255)->comment('出典元サイト名');
            $table->string('alt_text', 512)->comment('alt属性テキスト');
            $table->dateTime('created_at')->comment('作成日時');

            $table->index('resource_snapshot_identifier', 'idx_resource_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_image_snapshots');
        Schema::dropIfExists('draft_wiki_images');
        Schema::dropIfExists('wiki_images');
    }
};
