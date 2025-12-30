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
            $table->string('id', 26)->primary()->comment('楽曲ID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 64)->comment('楽曲名');
            $table->string('agency_id', 26)->nullable()->comment('所属事務所ID');
            $table->json('belong_identifiers')->comment('所属グループ/タレントリスト')->default('[]');
            $table->string('lyricist', 32)->comment('作詞者')->default('');
            $table->string('composer', 32)->comment('作曲者')->default('');
            $table->date('release_date')->nullable()->comment('リリース日');
            $table->text('overview')->comment('概要')->default('');
            $table->string('cover_image_path', 255)->nullable()->comment('カバー画像パス');
            $table->string('music_video_link', 255)->nullable()->comment('MV リンク');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->timestamps();
        });

        Schema::create('draft_songs', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('楽曲ID');
            $table->string('published_id', 26)->nullable()->comment('公開済み楽曲ID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('editor_id', 26)->comment('編集者ID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 64)->comment('楽曲名');
            $table->string('agency_id', 26)->nullable()->comment('所属事務所ID');
            $table->json('belong_identifiers')->comment('所属グループ/タレントリスト')->default('[]');
            $table->string('lyricist', 32)->comment('作詞者')->default('');
            $table->string('composer', 32)->comment('作曲者')->default('');
            $table->date('release_date')->nullable()->comment('リリース日');
            $table->text('overview')->comment('概要')->default('');
            $table->string('cover_image_path', 255)->nullable()->comment('カバー画像パス');
            $table->string('music_video_link', 255)->nullable()->comment('MV リンク');
            $table->text('status')->comment('公開ステータス');
            $table->timestamps();
        });

        Schema::create('song_snapshots', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('スナップショットID');
            $table->string('song_id', 26)->index()->comment('公開済み楽曲ID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 64)->comment('楽曲名');
            $table->string('agency_id', 26)->nullable()->comment('所属事務所ID');
            $table->json('belong_identifiers')->comment('所属グループ/タレントリスト')->default('[]');
            $table->string('lyricist', 32)->comment('作詞者')->default('');
            $table->string('composer', 32)->comment('作曲者')->default('');
            $table->date('release_date')->nullable()->comment('リリース日');
            $table->text('overview')->comment('概要')->default('');
            $table->string('cover_image_path', 255)->nullable()->comment('カバー画像パス');
            $table->string('music_video_link', 255)->nullable()->comment('MV リンク');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->dateTime('created_at')->comment('作成日時');

            $table->unique(['song_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_snapshots');
        Schema::dropIfExists('draft_songs');
        Schema::dropIfExists('songs');
    }
};
