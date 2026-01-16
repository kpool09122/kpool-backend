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
        Schema::create('announcements', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('お知らせID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->unsignedTinyInteger('category')->comment('カテゴリ');
            $table->string('title', 64)->comment('タイトル');
            $table->text('content')->comment('本文');
            $table->dateTime('published_date')->comment('公開日時');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('draft_announcements', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('お知らせID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->unsignedTinyInteger('category')->comment('カテゴリ');
            $table->string('title', 64)->comment('タイトル');
            $table->text('content')->comment('本文');
            $table->dateTime('published_date')->comment('公開予定日時');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('draft_announcements');
    }
};

