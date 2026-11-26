<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('monetization_registered_payment_methods', 'monetization_payment_methods');

        Schema::table('monetization_payment_methods', static function (Blueprint $table): void {
            $table->renameColumn('stripe_payment_method_id', 'external_payment_method_id');
            $table->dropUnique('monetization_registered_payment_methods_stripe_payment_method_id_unique');
            $table->unique(['monetization_account_id', 'external_payment_method_id'], 'payment_methods_account_external_unique');
        });

        Schema::table('payments', static function (Blueprint $table): void {
            $table->dropColumn(['payment_method_type', 'payment_method_label', 'payment_method_recurring_enabled', 'stripe_payment_method_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', static function (Blueprint $table): void {
            $table->string('payment_method_type', 32)->comment('決済手段タイプ');
            $table->string('payment_method_label', 255)->comment('決済手段ラベル');
            $table->boolean('payment_method_recurring_enabled')->comment('継続課金有効フラグ');
            $table->string('stripe_payment_method_id', 64)->nullable()->comment('Stripe Payment Method ID');
        });

        Schema::table('monetization_payment_methods', static function (Blueprint $table): void {
            $table->dropUnique('payment_methods_account_external_unique');
            $table->unique('external_payment_method_id', 'monetization_registered_payment_methods_stripe_payment_method_id_unique');
            $table->renameColumn('external_payment_method_id', 'stripe_payment_method_id');
        });

        Schema::rename('monetization_payment_methods', 'monetization_registered_payment_methods');
    }
};
