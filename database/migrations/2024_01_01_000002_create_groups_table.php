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
            $table->uuid('id')->primary()->comment('グループID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('translation', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('グループ名');
            $table->string('normalized_name', 32)->comment('正規化されたグループ名');
            $table->uuid('agency_id')->nullable()->comment('所属事務所ID');
            $table->text('description')->comment('概要')->default('');
            $table->string('image_path', 255)->nullable()->comment('画像パス');
            $table->boolean('is_official')->default(false)->comment('Official flag');
            $table->uuid('owner_account_id')->nullable()->comment('Owner account ID');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->timestamps();
        });

        Schema::create('draft_groups', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('グループID');
            $table->uuid('published_id')->nullable()->comment('公開済みグループID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->uuid('editor_id')->comment('編集者ID');
            $table->string('translation', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('グループ名');
            $table->string('normalized_name', 32)->comment('正規化されたグループ名');
            $table->uuid('agency_id')->nullable()->comment('所属事務所ID');
            $table->text('description')->comment('概要')->default('');
            $table->string('image_path', 255)->nullable()->comment('画像パス');
            $table->text('status')->comment('公開ステータス');
            $table->timestamps();
        });

        Schema::create('group_snapshots', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('スナップショットID');
            $table->uuid('group_id')->index()->comment('公開済みグループID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('translation', 8)->comment('翻訳言語');
            $table->string('name', 32)->comment('グループ名');
            $table->string('normalized_name', 32)->comment('正規化されたグループ名');
            $table->uuid('agency_id')->nullable()->comment('所属事務所ID');
            $table->text('description')->comment('概要')->default('');
            $table->string('image_path', 255)->nullable()->comment('画像パス');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->dateTime('created_at')->comment('作成日時');

            $table->unique(['group_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_snapshots');
        Schema::dropIfExists('draft_groups');
        Schema::dropIfExists('groups');
    }
};

