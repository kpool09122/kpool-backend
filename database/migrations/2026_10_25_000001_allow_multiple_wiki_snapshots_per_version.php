<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wiki_snapshots', static function (Blueprint $table): void {
            $table->index(['wiki_id', 'version', 'created_at', 'id'], 'wiki_snapshots_version_order_index');
            $table->dropUnique(['wiki_id', 'version']);
        });
    }

    public function down(): void
    {
        if (DB::table('wiki_snapshots')->select('wiki_id', 'version')->groupBy('wiki_id', 'version')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Cannot restore the unique constraint while duplicate wiki/version snapshots exist. Export and preserve all history, then explicitly reconcile duplicates before retrying rollback.');
        }
        Schema::table('wiki_snapshots', static function (Blueprint $table): void {
            $table->unique(['wiki_id', 'version']);
            $table->dropIndex('wiki_snapshots_version_order_index');
        });
    }
};
