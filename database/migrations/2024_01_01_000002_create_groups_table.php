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
        Schema::create('groups', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('グループID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('translation', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('グループ名');
            $table->string('agency_id', 26)->nullable()->comment('所属事務所ID');
            $table->text('description')->comment('概要')->default('');
            $table->json('song_identifiers')->comment('グループの持ち歌リスト')->default('[]');
            $table->string('image_path', 255)->nullable()->comment('画像パス');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->timestamps();
        });

        Schema::create('draft_groups', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('グループID');
            $table->string('published_id', 26)->nullable()->comment('公開済みグループID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('editor_id', 26)->comment('編集者ID');
            $table->string('translation', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('グループ名');
            $table->string('agency_id', 26)->nullable()->comment('所属事務所ID');
            $table->text('description')->comment('概要')->default('');
            $table->json('song_identifiers')->comment('グループの持ち歌リスト')->default('[]');
            $table->string('image_path', 255)->nullable()->comment('画像パス');
            $table->text('status')->comment('公開ステータス');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
        Schema::dropIfExists('draft_groups');
    }
};

