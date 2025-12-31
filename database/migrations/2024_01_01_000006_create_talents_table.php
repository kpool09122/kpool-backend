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
        Schema::create('talents', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('タレントID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('タレント名');
            $table->string('real_name', 32)->comment('本名')->default('');
            $table->uuid('agency_id')->nullable()->comment('所属事務所ID');
            $table->json('group_identifiers')->comment('所属グループリスト')->default('[]');
            $table->date('birthday')->nullable()->comment('誕生日');
            $table->text('career')->comment('経歴')->default('');
            $table->string('image_link', 255)->nullable()->comment('画像パス');
            $table->json('relevant_video_links')->comment('関連動画リンク')->default('[]');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->timestamps();
        });

        Schema::create('draft_talents', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('タレントID');
            $table->uuid('published_id')->nullable()->comment('公開済みタレントID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->uuid('editor_id')->comment('編集者ID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('タレント名');
            $table->string('real_name', 32)->comment('本名')->default('');
            $table->uuid('agency_id')->nullable()->comment('所属事務所ID');
            $table->json('group_identifiers')->comment('所属グループリスト')->default('[]');
            $table->date('birthday')->nullable()->comment('誕生日');
            $table->text('career')->comment('経歴')->default('');
            $table->string('image_link', 255)->nullable()->comment('画像パス');
            $table->json('relevant_video_links')->comment('関連動画リンク')->default('[]');
            $table->text('status')->comment('公開ステータス');
            $table->timestamps();
        });

        Schema::create('talent_snapshots', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('スナップショットID');
            $table->uuid('talent_id')->index()->comment('公開済みタレントID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('タレント名');
            $table->string('real_name', 32)->comment('本名')->default('');
            $table->uuid('agency_id')->nullable()->comment('所属事務所ID');
            $table->json('group_identifiers')->comment('所属グループリスト')->default('[]');
            $table->date('birthday')->nullable()->comment('誕生日');
            $table->text('career')->comment('経歴')->default('');
            $table->string('image_link', 255)->nullable()->comment('画像パス');
            $table->json('relevant_video_links')->comment('関連動画リンク')->default('[]');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->dateTime('created_at')->comment('作成日時');

            $table->unique(['talent_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talent_snapshots');
        Schema::dropIfExists('draft_talents');
        Schema::dropIfExists('talents');
    }
};
