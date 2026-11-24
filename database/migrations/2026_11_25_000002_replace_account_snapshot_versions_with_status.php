<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('account_snapshots', function (Blueprint $table): void {
            $table->string('status')->default('approved');
            $table->dropUnique(['account_id', 'version']);
            $table->dropColumn('version');
            $table->timestampTz('saved_at', 6)->change();
            $table->index(['account_id', 'status', 'saved_at', 'id'], 'account_snapshots_current_index');
        });
    }

    public function down(): void
    {
        if (DB::table('account_snapshots')->where('status', 'rejected')->exists()) {
            throw new RuntimeException('Cannot restore version-only snapshots while rejected history exists.');
        }

        Schema::table('account_snapshots', function (Blueprint $table): void {
            $table->unsignedInteger('version')->nullable();
        });
        DB::statement('UPDATE account_snapshots AS s SET version = ranked.version FROM (SELECT id, ROW_NUMBER() OVER (PARTITION BY account_id ORDER BY saved_at, id) AS version FROM account_snapshots) AS ranked WHERE s.id = ranked.id');
        Schema::table('account_snapshots', function (Blueprint $table): void {
            $table->unsignedInteger('version')->nullable(false)->change();
            $table->unique(['account_id', 'version']);
            $table->dropIndex('account_snapshots_current_index');
            $table->dropColumn('status');
        });
    }
};
