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
        Schema::create('wikis', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Wiki ID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('slug', 80)->comment('URLスラッグ');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('resource_type', 50)->comment('リソースタイプ (agency, group, talent, song)');
            $table->jsonb('sections')->default('[]')->comment('セクションコレクション');
            $table->string('theme_color', 7)->nullable()->comment('テーマカラー (#RRGGBB)');
            $table->unsignedInteger('version')->default(1)->comment('バージョン');
            $table->uuid('owner_account_id')->nullable()->comment('Owner account ID');
            $table->uuid('editor_id')->nullable()->comment('編集者ID');
            $table->uuid('approver_id')->nullable()->comment('承認者ID');
            $table->uuid('merger_id')->nullable()->comment('マージ者ID');
            $table->uuid('source_editor_id')->nullable()->comment('翻訳元編集者ID');
            $table->dateTime('merged_at')->nullable()->comment('マージ日時');
            $table->dateTime('translated_at')->nullable()->comment('翻訳日時');
            $table->dateTime('approved_at')->nullable()->comment('承認日時');
            $table->dateTime('published_at')->nullable()->comment('公開日時');
            $table->timestamps();

            $table->unique(['slug', 'language']);
            $table->index('translation_set_identifier');
            $table->index('resource_type');
        });

        Schema::create('draft_wikis', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Draft Wiki ID');
            $table->uuid('published_wiki_id')->nullable()->comment('公開済みWiki ID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('slug', 80)->comment('URLスラッグ');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('resource_type', 50)->comment('リソースタイプ (agency, group, talent, song)');
            $table->jsonb('sections')->default('[]')->comment('セクションコレクション');
            $table->string('theme_color', 7)->nullable()->comment('テーマカラー (#RRGGBB)');
            $table->string('status', 50)->default('pending')->comment('承認ステータス');
            $table->uuid('editor_id')->nullable()->comment('編集者ID');
            $table->uuid('approver_id')->nullable()->comment('承認者ID');
            $table->uuid('merger_id')->nullable()->comment('マージ者ID');
            $table->uuid('source_editor_id')->nullable()->comment('翻訳元編集者ID');
            $table->dateTime('edited_at')->nullable()->comment('編集日時');
            $table->dateTime('merged_at')->nullable()->comment('マージ日時');
            $table->dateTime('translated_at')->nullable()->comment('翻訳日時');
            $table->dateTime('approved_at')->nullable()->comment('承認日時');
            $table->timestamps();

            $table->foreign('published_wiki_id')
                ->references('id')
                ->on('wikis')
                ->onDelete('set null');

            $table->index('translation_set_identifier');
            $table->index('resource_type');
            $table->index('status');
            $table->index('editor_id');
        });

        Schema::create('wiki_snapshots', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('スナップショットID');
            $table->uuid('wiki_id')->index()->comment('公開済みWiki ID');
            $table->uuid('translation_set_identifier')->comment('翻訳セットID');
            $table->string('slug', 80)->comment('URLスラッグ');
            $table->string('language', 8)->comment('翻訳言語');
            $table->string('resource_type', 50)->comment('リソースタイプ');
            $table->jsonb('sections')->default('[]')->comment('セクションコレクション');
            $table->string('theme_color', 7)->nullable()->comment('テーマカラー');
            $table->unsignedInteger('version')->comment('バージョン');
            $table->uuid('editor_id')->nullable()->comment('編集者ID');
            $table->uuid('approver_id')->nullable()->comment('承認者ID');
            $table->uuid('merger_id')->nullable()->comment('マージ者ID');
            $table->uuid('source_editor_id')->nullable()->comment('翻訳元編集者ID');
            $table->dateTime('merged_at')->nullable()->comment('マージ日時');
            $table->dateTime('translated_at')->nullable()->comment('翻訳日時');
            $table->dateTime('approved_at')->nullable()->comment('承認日時');
            $table->timestamp('created_at')->comment('作成日時');

            $table->unique(['wiki_id', 'version']);

            $table->foreign('wiki_id')
                ->references('id')
                ->on('wikis')
                ->onDelete('cascade');
        });

        Schema::create('wiki_histories', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('履歴ID');
            $table->string('action_type', 50)->comment('アクションタイプ (draft_status_change, publish, rollback)');
            $table->uuid('actor_id')->comment('実行者ID');
            $table->uuid('submitter_id')->nullable()->comment('提出者ID');
            $table->uuid('wiki_id')->nullable()->comment('公開済みWiki ID');
            $table->uuid('draft_wiki_id')->nullable()->comment('下書きWiki ID');
            $table->string('from_status', 50)->nullable()->comment('変更前ステータス');
            $table->string('to_status', 50)->nullable()->comment('変更後ステータス');
            $table->unsignedInteger('from_version')->nullable()->comment('変更前バージョン');
            $table->unsignedInteger('to_version')->nullable()->comment('変更後バージョン');
            $table->string('subject_name', 64)->comment('対象名');
            $table->dateTime('recorded_at')->comment('記録日時');

            $table->index('wiki_id');
            $table->index('draft_wiki_id');
            $table->index('actor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_histories');
        Schema::dropIfExists('wiki_snapshots');
        Schema::dropIfExists('draft_wikis');
        Schema::dropIfExists('wikis');
    }
};
