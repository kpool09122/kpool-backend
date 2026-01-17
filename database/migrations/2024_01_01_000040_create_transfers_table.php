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
        Schema::create('transfers', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Transfer ID');
            $table->uuid('settlement_batch_id')->comment('SettlementBatch ID');
            $table->uuid('monetization_account_id')->comment('MonetizationAccount ID');
            $table->string('currency', 3)->comment('Currency (JPY, USD, KRW)');
            $table->unsignedBigInteger('amount')->comment('Transfer Amount');
            $table->string('status', 32)->comment('Status (pending, sent, failed)');
            $table->timestamp('sent_at')->nullable()->comment('Sent At');
            $table->timestamp('failed_at')->nullable()->comment('Failed At');
            $table->text('failure_reason')->nullable()->comment('Failure Reason');
            $table->string('stripe_transfer_id', 64)->nullable()->comment('Stripe Transfer ID');
            $table->timestamps();

            $table->foreign('settlement_batch_id')->references('id')->on('settlement_batches')->onDelete('cascade');
            $table->foreign('monetization_account_id')->references('id')->on('monetization_accounts')->onDelete('cascade');
            $table->index('settlement_batch_id');
            $table->index('monetization_account_id');
            $table->index('status');
            $table->index('stripe_transfer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
