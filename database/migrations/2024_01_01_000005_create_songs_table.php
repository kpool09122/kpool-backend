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
            $table->uuid('id')->primary()->comment('歌ID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 64)->comment('歌名');
            $table->uuid('agency_id')->nullable()->comment('所有事務所ID');
            $table->string('lyricist', 32)->comment('作詞者')->default('');
            $table->string('composer', 32)->comment('作曲者')->default('');
            $table->date('release_date')->nullable()->comment('リリース日');
            $table->text('lyrics')->default('')->comment('歌詞');
            $table->text('overview')->comment('概要')->default('');
            $table->string('cover_image_path', 255)->nullable()->comment('カバー画像パス');
            $table->string('music_video_link', 255)->nullable()->comment('MV リンク');
            $table->boolean('is_official')->default(false)->comment('Official flag');
            $table->uuid('owner_account_id')->nullable()->comment('Owner account ID');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('draft_songs', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('歌ID');
            $table->uuid('published_id')->nullable()->comment('公開済み歌ID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->uuid('editor_id')->comment('編集者ID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 64)->comment('楽曲名');
            $table->uuid('agency_id')->nullable()->comment('所属事務所ID');
            $table->string('lyricist', 32)->comment('作詞者')->default('');
            $table->string('composer', 32)->comment('作曲者')->default('');
            $table->date('release_date')->nullable()->comment('リリース日');
            $table->text('lyrics')->default('')->comment('歌詞');
            $table->text('overview')->comment('概要')->default('');
            $table->string('cover_image_path', 255)->nullable()->comment('カバー画像パス');
            $table->string('music_video_link', 255)->nullable()->comment('MV リンク');
            $table->text('status')->comment('公開ステータス');
            $table->timestamps();
        });

        Schema::create('song_snapshots', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('スナップショットID');
            $table->uuid('song_id')->index()->comment('公開済み歌ID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 64)->comment('歌名');
            $table->uuid('agency_id')->nullable()->comment('所属事務所ID');
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
