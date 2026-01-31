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
            $table->boolean('is_hidden')->default(false)->comment('非表示フラグ');
            $table->uuid('hidden_by')->nullable()->comment('非表示実行者ID');
            $table->timestamp('hidden_at')->nullable()->comment('非表示日時');
            $table->uuid('uploader_id')->comment('アップロード者ID');
            $table->timestamp('uploaded_at')->comment('アップロード日時');
            $table->uuid('approver_id')->nullable()->comment('承認者ID');
            $table->timestamp('approved_at')->nullable()->comment('承認日時');
            $table->uuid('updater_id')->nullable()->comment('更新者ID');
            $table->timestamp('updated_at')->nullable()->comment('更新日時');

            $table->index(['resource_type', 'resource_identifier'], 'idx_resource');
            $table->index('image_usage', 'idx_usage');
        });

        Schema::create('draft_wiki_images', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('画像ID');
            $table->uuid('published_id')->nullable()->comment('公開済み画像ID');
            $table->string('resource_type', 16)->comment('リソースタイプ (talent, song, group)');
            $table->uuid('draft_resource_identifier')->comment('下書きリソースID');
            $table->uuid('uploader_id')->comment('アップロード者ID');
            $table->string('image_path', 255)->comment('画像パス');
            $table->string('image_usage', 16)->comment('画像用途 (profile, cover, logo, additional)');
            $table->integer('display_order')->default(0)->comment('表示順');
            $table->string('source_url', 512)->comment('出典元URL');
            $table->string('source_name', 255)->comment('出典元サイト名');
            $table->string('alt_text', 512)->comment('alt属性テキスト');
            $table->string('status', 16)->default('pending')->comment('承認ステータス (pending, under_review, approved, rejected)');
            $table->timestamp('agreed_to_terms_at')->comment('規約同意日時');
            $table->timestamp('uploaded_at')->comment('アップロード日時');

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
            $table->uuid('uploader_id')->comment('アップロード者ID');
            $table->timestamp('uploaded_at')->comment('アップロード日時');
            $table->uuid('approver_id')->nullable()->comment('承認者ID');
            $table->timestamp('approved_at')->nullable()->comment('承認日時');
            $table->uuid('updater_id')->nullable()->comment('更新者ID');
            $table->timestamp('updated_at')->nullable()->comment('更新日時');

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
