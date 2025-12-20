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
            $table->string('id', 26)->primary()->comment('タレントID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('language', 8)->comment('言語');
            $table->string('name', 32)->comment('芸名');
            $table->string('real_name', 32)->comment('本名');
            $table->string('agency_id', 26)->nullable()->comment('所属事務所ID');
            $table->json('group_identifiers')->default('[]')->comment('所属グループID一覧');
            $table->date('birthday')->nullable()->comment('誕生日');
            $table->string('career', 2048)->default('')->comment('経歴');
            $table->string('image_link', 255)->nullable()->comment('画像リンク');
            $table->json('relevant_video_links')->default('[]')->comment('関連動画リンク一覧');
            $table->unsignedInteger('version')->default(1)->comment('バージョン');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('draft_talents', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('タレントID');
            $table->string('published_id', 26)->nullable()->comment('公開済みタレントID');
            $table->string('translation_set_identifier', 26)->comment('翻訳セットID');
            $table->string('editor_id', 26)->comment('編集者ID');
            $table->string('language', 8)->comment('言語');
            $table->string('name', 32)->comment('芸名');
            $table->string('real_name', 32)->comment('本名');
            $table->string('agency_id', 26)->nullable()->comment('所属事務所ID');
            $table->json('group_identifiers')->default('[]')->comment('所属グループID一覧');
            $table->date('birthday')->nullable()->comment('誕生日');
            $table->string('career', 2048)->default('')->comment('経歴');
            $table->string('image_link', 255)->nullable()->comment('画像リンク');
            $table->json('relevant_video_links')->default('[]')->comment('関連動画リンク一覧');
            $table->text('status')->comment('公開ステータス');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_talents');
        Schema::dropIfExists('talents');
    }
};

