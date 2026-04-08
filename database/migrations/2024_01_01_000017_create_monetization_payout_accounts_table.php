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
        Schema::create('monetization_payout_accounts', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('PayoutAccount ID');
            $table->uuid('monetization_account_id')->comment('MonetizationAccount ID');
            $table->string('stripe_external_account_id', 255)->unique()->comment('Stripe External Account ID');
            $table->string('bank_name', 128)->nullable()->comment('Bank Name');
            $table->string('last4', 4)->nullable()->comment('Last 4 digits');
            $table->string('country', 2)->nullable()->comment('Country Code');
            $table->string('currency', 3)->nullable()->comment('Currency Code');
            $table->string('account_holder_type', 32)->nullable()->comment('Account Holder Type (individual, company)');
            $table->boolean('is_default')->default(false)->comment('Is Default Payout Account');
            $table->string('status', 32)->default('active')->comment('Status (active, inactive)');
            $table->timestamps();

            $table->foreign('monetization_account_id')->references('id')->on('monetization_accounts')->onDelete('cascade');
            $table->index('monetization_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monetization_payout_accounts');
    }
};
