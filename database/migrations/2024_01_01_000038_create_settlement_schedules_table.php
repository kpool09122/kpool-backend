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
        Schema::create('settlement_schedules', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('SettlementSchedule ID');
            $table->uuid('monetization_account_id')->comment('MonetizationAccount ID');
            $table->string('interval', 32)->comment('Settlement Interval (monthly, biweekly, threshold)');
            $table->unsignedInteger('payout_delay_days')->default(0)->comment('Payout Delay Days');
            $table->unsignedBigInteger('threshold_amount')->nullable()->comment('Threshold Amount');
            $table->string('threshold_currency', 3)->nullable()->comment('Threshold Currency');
            $table->date('next_closing_date')->comment('Next Closing Date');
            $table->timestamps();

            $table->foreign('monetization_account_id')->references('id')->on('monetization_accounts')->onDelete('cascade');
            $table->index('monetization_account_id');
            $table->index('next_closing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_schedules');
    }
};
