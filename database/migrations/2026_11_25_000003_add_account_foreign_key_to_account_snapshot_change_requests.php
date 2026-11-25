<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('account_snapshot_change_requests', static function (Blueprint $table): void {
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('account_snapshot_change_requests', static function (Blueprint $table): void {
            $table->dropForeign(['account_id']);
        });
    }
};
