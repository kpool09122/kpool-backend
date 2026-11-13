<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('wiki_image_snapshots');
    }

    public function down(): void
    {
        // Recreate the schema for older application versions; deleted history cannot be restored.
        Schema::create('wiki_image_snapshots', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('スナップショットID');
            $table->uuid('image_id')->comment('画像ID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('image_path', 255)->comment('画像パス');
            $table->integer('display_order')->comment('表示順');
            $table->string('source_url', 512)->comment('出典元URL');
            $table->string('source_name', 255)->comment('出典元サイト名');
            $table->string('author', 255)->nullable()->comment('画像の作者');
            $table->string('license', 32)->nullable()->comment('画像のライセンス識別子');
            $table->string('alt_text', 512)->comment('alt属性テキスト');
            $table->uuid('uploader_id')->comment('アップロード者ID');
            $table->timestamp('uploaded_at')->comment('アップロード日時');
            $table->boolean('rights_confirmation_agreed')->comment('著作権・肖像権確認同意');
            $table->uuid('approver_id')->nullable()->comment('承認者ID');
            $table->timestamp('approved_at')->nullable()->comment('承認日時');
            $table->uuid('updater_id')->nullable()->comment('更新者ID');
            $table->timestamp('updated_at')->nullable()->comment('更新日時');

            $table->index('translation_set_identifier', 'idx_resource_snapshot');
        });
    }
};
