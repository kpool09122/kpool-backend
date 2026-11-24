<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_snapshot_change_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id')->index();
            $table->uuid('snapshot_id');
            $table->uuid('reviewed_snapshot_id')->nullable();
            $table->text('change_reason');
            $table->string('status');
            $table->timestampTz('requested_at', 6);
            $table->uuid('reviewed_by')->nullable();
            $table->timestampTz('reviewed_at', 6)->nullable();
            $table->jsonb('rejection_reason')->nullable();

            $table->index(['account_id', 'status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('account_snapshot_change_requests');
    }
};
