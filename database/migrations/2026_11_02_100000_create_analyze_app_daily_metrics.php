<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyze_app_daily_summaries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->date('day');
            $table->uuid('revision');
            $table->decimal('application_users', 30, 9);
            $table->decimal('registered_users', 30, 9);
            $table->timestampTz('registered_users_snapshot_at', 6);
            $table->jsonb('measurements');
            $table->timestampsTz(6);
            $table->unique(['day'], 'analyze_app_summary_identity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyze_app_daily_summaries');
    }
};
