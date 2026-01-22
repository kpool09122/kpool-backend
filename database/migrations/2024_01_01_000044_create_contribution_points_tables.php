<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // contribution_point_histories: 付与履歴（INSERT only、監査/分析用）
        Schema::create('contribution_point_histories', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('principal_id')->index()->comment('プリンシパルID');
            $table->string('year_month', 7)->comment('年月（YYYY-MM）');
            $table->integer('points')->comment('付与ポイント');
            $table->string('resource_type', 50)->comment('リソースタイプ（agency, talent, group, song）');
            $table->uuid('resource_id')->comment('リソースID');
            $table->string('contributor_type', 50)->comment('貢献タイプ（editor, approver, merger）');
            $table->boolean('is_new_creation')->comment('新規作成かどうか');
            $table->timestamp('created_at')->nullable();

            $table->foreign('principal_id')
                ->references('id')
                ->on('wiki_principals')
                ->cascadeOnDelete();

            $table->index(['principal_id', 'year_month']);
            $table->index(['resource_type', 'resource_id', 'contributor_type', 'principal_id'], 'cooldown_check_index');
        });

        // contribution_point_summaries: 月別集計サマリー（月次バッチで更新、昇格降格判定に使用）
        Schema::create('contribution_point_summaries', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('principal_id')->index()->comment('プリンシパルID');
            $table->string('year_month', 7)->comment('年月（YYYY-MM）');
            $table->integer('points')->default(0)->comment('累計ポイント');
            $table->timestamps();

            $table->foreign('principal_id')
                ->references('id')
                ->on('wiki_principals')
                ->cascadeOnDelete();

            $table->unique(['principal_id', 'year_month']);
        });

        // promotion_histories: 昇格・降格履歴
        Schema::create('promotion_histories', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('principal_id')->index()->comment('プリンシパルID');
            $table->string('from_role', 50)->comment('変更前の役割');
            $table->string('to_role', 50)->comment('変更後の役割');
            $table->string('reason', 255)->nullable()->comment('理由');
            $table->timestamp('processed_at')->comment('処理日時');

            $table->foreign('principal_id')
                ->references('id')
                ->on('wiki_principals')
                ->cascadeOnDelete();
        });

        // demotion_warnings: 降格警告カウンター
        Schema::create('demotion_warnings', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('principal_id')->comment('プリンシパルID');
            $table->integer('warning_count')->default(0)->comment('警告回数');
            $table->string('last_warning_month', 7)->comment('最終警告月（YYYY-MM）');
            $table->timestamps();

            $table->foreign('principal_id')
                ->references('id')
                ->on('wiki_principals')
                ->cascadeOnDelete();

            $table->unique('principal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demotion_warnings');
        Schema::dropIfExists('promotion_histories');
        Schema::dropIfExists('contribution_point_summaries');
        Schema::dropIfExists('contribution_point_histories');
    }
};
