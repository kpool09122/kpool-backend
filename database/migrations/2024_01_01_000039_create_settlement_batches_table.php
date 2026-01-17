<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settlement_batches', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('SettlementBatch ID');
            $table->uuid('monetization_account_id')->comment('MonetizationAccount ID');
            $table->string('currency', 3)->comment('Currency (JPY, USD, KRW)');
            $table->unsignedBigInteger('gross_amount')->comment('Gross Amount');
            $table->unsignedBigInteger('fee_amount')->comment('Fee Amount');
            $table->unsignedBigInteger('net_amount')->comment('Net Amount');
            $table->date('period_start')->comment('Period Start Date');
            $table->date('period_end')->comment('Period End Date');
            $table->string('status', 32)->comment('Status (pending, processing, paid, failed)');
            $table->timestamp('processed_at')->nullable()->comment('Processed At');
            $table->timestamp('paid_at')->nullable()->comment('Paid At');
            $table->timestamp('failed_at')->nullable()->comment('Failed At');
            $table->text('failure_reason')->nullable()->comment('Failure Reason');
            $table->timestamps();

            $table->foreign('monetization_account_id')->references('id')->on('monetization_accounts')->onDelete('cascade');
            $table->index('monetization_account_id');
            $table->index('status');
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_batches');
    }
};
