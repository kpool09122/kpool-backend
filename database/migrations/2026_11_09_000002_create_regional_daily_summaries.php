<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analyze_regional_daily_summaries', static function (Blueprint $table): void {
            $table->string('context', 4);
            $table->date('day');
            $table->string('country', 2);
            $table->string('region', 16);
            $table->string('wiki_identifier', 36)->default('');
            $table->string('translation_set_identifier', 36)->default('');
            $table->string('language', 35)->default('');
            $table->string('metric', 32);
            $table->string('source', 80)->default('ga4');
            $table->string('definition_version', 80)->default('1');
            $table->decimal('value', 30, 9)->nullable();
            $table->unsignedBigInteger('denominator')->nullable();
            $table->jsonb('quality');
            $table->uuid('revision');
            $table->timestampTz('updated_at', 6);
            $table->primary(['context', 'day', 'country', 'region', 'wiki_identifier', 'language', 'metric'], 'analyze_regional_grain_primary');
            $table->index(['context', 'country', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyze_regional_daily_summaries');
    }
};
