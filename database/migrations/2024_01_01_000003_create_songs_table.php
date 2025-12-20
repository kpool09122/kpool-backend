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
        Schema::create('songs', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('歌ID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('language', 8)->comment('言語');
            $table->string('name', 64)->comment('歌名');
            $table->string('agency_id', 26)->nullable()->comment('所属事務所ID');
            $table->json('belong_identifiers')->default('[]')->comment('タレント/グループID一覧');
            $table->string('lyricist', 32)->default('')->comment('作詞者');
            $table->string('composer', 32)->default('')->comment('作曲者');
            $table->date('release_date')->nullable()->comment('リリース日');
            $table->text('lyrics')->nullable()->comment('歌詞');
            $table->string('overview', 512)->default('')->comment('概要');
            $table->string('cover_image_path', 255)->nullable()->comment('カバー画像パス');
            $table->string('music_video_link', 255)->nullable()->comment('MVリンク');
            $table->unsignedInteger('version')->default(1)->comment('バージョン');
            $table->timestamps();
        });

        Schema::create('draft_songs', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('歌ID');
            $table->string('published_id', 26)->nullable()->comment('公開済み歌ID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('editor_id', 26)->comment('編集者ID');
            $table->string('language', 8)->comment('言語');
            $table->string('name', 64)->comment('歌名');
            $table->string('agency_id', 26)->nullable()->comment('所属事務所ID');
            $table->json('belong_identifiers')->default('[]')->comment('タレント/グループID一覧');
            $table->string('lyricist', 32)->default('')->comment('作詞者');
            $table->string('composer', 32)->default('')->comment('作曲者');
            $table->date('release_date')->nullable()->comment('リリース日');
            $table->text('lyrics')->nullable()->comment('歌詞');
            $table->string('overview', 512)->default('')->comment('概要');
            $table->string('cover_image_path', 255)->nullable()->comment('カバー画像パス');
            $table->string('music_video_link', 255)->nullable()->comment('MVリンク');
            $table->text('status')->comment('公開ステータス');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_songs');
        Schema::dropIfExists('songs');
    }
};

