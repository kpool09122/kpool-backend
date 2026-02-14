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
        Schema::create('monetization_registered_payment_methods', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('RegisteredPaymentMethod ID');
            $table->uuid('monetization_account_id')->comment('MonetizationAccount ID');
            $table->string('stripe_payment_method_id', 255)->unique()->comment('Stripe Payment Method ID');
            $table->string('type', 32)->comment('Payment Method Type (card)');
            $table->string('brand', 64)->nullable()->comment('Card Brand');
            $table->string('last4', 4)->nullable()->comment('Last 4 digits');
            $table->unsignedTinyInteger('exp_month')->nullable()->comment('Expiration Month');
            $table->unsignedSmallInteger('exp_year')->nullable()->comment('Expiration Year');
            $table->boolean('is_default')->default(false)->comment('Is Default Payment Method');
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
        Schema::dropIfExists('monetization_payment_methods');
    }
};
