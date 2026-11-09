<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analyze_app_daily_summaries', function (Blueprint $table): void {
            // Null means this historical import has no country breakdown.
            $table->jsonb('country_metrics')->nullable();
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
            $table->dropColumn('country_metrics');
        });
        foreach (['identities', 'archived_identities'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropColumn('registration_country');
            });
        }
    }
};
