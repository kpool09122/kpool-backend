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
        // accounts テーブルを先に作成
        Schema::create('accounts', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Account ID');
            $table->string('email', 255)->unique()->comment('Account Email');
            $table->string('type', 32)->comment('Account Type (corporation, individual)');
            $table->string('name', 64)->comment('Account Name');
            $table->string('status', 32)->comment('Account Status (active, pending, suspended)');
            $table->string('category', 32)->default('general')->comment('Account Category (agency, talent, general)');
            $table->json('contract_info')->comment('Contract Information (JSON)');
            $table->timestamps();

            $table->index('email');
            $table->index('status');
        });

        // monetization_accounts テーブル
        Schema::create('monetization_accounts', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('MonetizationAccount ID');
            $table->uuid('account_id')->unique()->comment('Account ID');
            $table->json('capabilities')->default('[]')->comment('Capabilities (PURCHASE, SELL, RECEIVE_PAYOUT)');
            $table->string('stripe_customer_id', 255)->nullable()->comment('Stripe Customer ID');
            $table->string('stripe_connected_account_id', 255)->nullable()->comment('Stripe Connected Account ID');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
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
        Schema::dropIfExists('accounts');
    }
};
