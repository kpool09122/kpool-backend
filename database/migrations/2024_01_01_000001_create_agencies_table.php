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
            $table->uuid('id')->primary()->comment('事務所ID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('事務所名');
            $table->string('normalized_name', 32)->comment('正規化された事務所名');
            $table->string('CEO', 32)->comment('CEO名')->default('');
            $table->string('normalized_CEO', 32)->comment('正規化されたCEO名')->default('');
            $table->date('founded_in')->nullable()->comment('設立年');
            $table->text('description')->comment('概要')->default('');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->timestamps();
        });

        Schema::create('draft_agencies', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('事務所ID');
            $table->uuid('published_id')->nullable()->comment('公開済み事務所ID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->uuid('editor_id')->comment('編集者ID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('事務所名');
            $table->string('normalized_name', 32)->comment('正規化された事務所名');
            $table->string('CEO', 32)->comment('CEO名')->default('');
            $table->string('normalized_CEO', 32)->comment('正規化されたCEO名')->default('');
            $table->date('founded_in')->nullable()->comment('設立年');
            $table->text('description')->comment('概要')->default('');
            $table->text('status')->comment('公開ステータス');
            $table->timestamps();
        });

        Schema::create('agency_snapshots', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('スナップショットID');
            $table->uuid('agency_id')->index()->comment('公開済み事務所ID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('事務所名');
            $table->string('normalized_name', 32)->comment('正規化された事務所名');
            $table->string('CEO', 32)->comment('CEO名')->default('');
            $table->string('normalized_CEO', 32)->comment('正規化されたCEO名')->default('');
            $table->date('founded_in')->nullable()->comment('設立年');
            $table->text('description')->comment('概要')->default('');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->dateTime('created_at')->comment('作成日時');

            $table->unique(['agency_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_snapshots');
        Schema::dropIfExists('draft_agencies');
        Schema::dropIfExists('agencies');
    }
}; 