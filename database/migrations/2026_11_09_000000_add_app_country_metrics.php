<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyze_app_country_daily_summaries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->date('day');
            // Empty string is the unknown country and participates in the unique constraint.
            $table->string('country', 2);
            $table->decimal('application_users', 30, 9);
            $table->decimal('registered_users', 30, 9);
            $table->unique(['day', 'country'], 'analyze_app_country_day_unique');
            $table->foreign('day')->references('day')->on('analyze_app_daily_summaries')->cascadeOnDelete();
        });
        Schema::table('analyze_app_daily_summaries', function (Blueprint $table): void {
            $table->dropColumn(['registered_users', 'registered_users_snapshot_at']);
        });
        foreach (['identities', 'archived_identities'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->char('registration_country', 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('analyze_app_daily_summaries', function (Blueprint $table): void {
            $table->decimal('registered_users', 30, 9)->nullable();
            $table->timestampTz('registered_users_snapshot_at', 6)->nullable();
        });
        Schema::dropIfExists('analyze_app_country_daily_summaries');
        foreach (['identities', 'archived_identities'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropColumn('registration_country');
            });
        }
    }
};
