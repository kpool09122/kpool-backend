<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_type_change_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->string('current_account_type');
            $table->string('requested_account_type');
            $table->string('status');
            $table->timestamp('requested_at');
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('rejection_reason')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('accounts')->cascadeOnDelete();
            $table->index(['account_id', 'status']);
        });

        DB::statement("CREATE UNIQUE INDEX account_type_change_requests_pending_unique ON account_type_change_requests (account_id) WHERE status = 'pending'");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS account_type_change_requests_pending_unique');
        Schema::dropIfExists('account_type_change_requests');
    }
};
