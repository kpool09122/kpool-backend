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
        Schema::create('monetization_accounts', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('MonetizationAccount ID');
            $table->uuid('account_id')->unique()->comment('Account ID');
            $table->json('capabilities')->default('[]')->comment('Capabilities (PURCHASE, SELL, RECEIVE_PAYOUT)');
            $table->string('stripe_customer_id', 255)->nullable()->comment('Stripe Customer ID');
            $table->string('stripe_connected_account_id', 255)->nullable()->comment('Stripe Connected Account ID');
            $table->timestamps();

            $table->index('account_id');
            $table->index('stripe_customer_id');
            $table->index('stripe_connected_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monetization_accounts');
    }
};
