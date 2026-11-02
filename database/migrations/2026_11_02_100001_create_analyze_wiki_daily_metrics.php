<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyze_wiki_daily_summaries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->date('day');
            $table->uuid('revision');
            $table->string('wiki_identifier', 128);
            $table->string('translation_set_identifier', 128);
            $table->string('language', 35);
            $table->string('country', 2);
            $table->decimal('views', 30, 9);
            $table->decimal('viewers', 30, 9);
            $table->decimal('engagement_seconds', 30, 9);
            $table->decimal('submissions', 30, 9);
            $table->decimal('publications', 30, 9);
            $table->unsignedBigInteger('engagement_denominator')->nullable();
            $table->jsonb('measurements');
            $table->timestampsTz(6);
            $table->unique(['day', 'wiki_identifier', 'language', 'country'], 'analyze_wiki_summary_identity');
            $table->index(['day', 'wiki_identifier', 'language', 'country'], 'analyze_wiki_summary_search');
            $table->index(['translation_set_identifier', 'day', 'language', 'country'], 'analyze_wiki_summary_translation');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyze_wiki_daily_summaries');
    }
};
