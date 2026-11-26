<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monetization_payment_method_archives', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('payment_method_id')->unique();
            $table->uuid('monetization_account_id')->index();
            $table->string('external_payment_method_id', 255);
            $table->string('type',32);
            $table->string('brand',64)->nullable();
            $table->string('last4',4)->nullable();
            $table->timestampTz('archived_at');
        });
    }
    public function down(): void { Schema::dropIfExists('monetization_payment_method_archives'); }
};
