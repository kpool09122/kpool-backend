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
        Schema::create('agencies', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('事務所ID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('translation', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('事務所名');
            $table->string('CEO', 32)->comment('CEO名')->default('');
            $table->date('founded_in')->nullable()->comment('設立年');
            $table->text('description')->comment('概要')->default('');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->timestamps();
        });

        Schema::create('draft_agencies', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('事務所ID');
            $table->string('published_id', 26)->nullable()->comment('公開済み事務所ID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('editor_id', 26)->comment('編集者ID');
            $table->string('translation', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('事務所名');
            $table->string('CEO', 32)->comment('CEO名')->default('');
            $table->date('founded_in')->nullable()->comment('設立年');
            $table->text('description')->comment('概要')->default('');
            $table->text('status')->comment('公開ステータス');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agencies');
        Schema::dropIfExists('agencies_pending');
    }
}; 