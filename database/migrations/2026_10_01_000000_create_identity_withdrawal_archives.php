<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_identities', static function (Blueprint $table): void {
            $table->uuid('identity_id')->primary();
            $table->string('language', 8);
            $table->timestamp('identity_created_at')->nullable();
            $table->timestamp('archived_at');
        });
        Schema::create('archived_accounts', static function (Blueprint $table): void {
            $table->uuid('account_id')->primary();
            $table->string('account_category', 32);
            $table->string('account_type', 32);
            $table->timestamp('archived_at');
        });
        Schema::create('archived_principals', static function (Blueprint $table): void {
            $table->uuid('identity_id');
            $table->string('principal_type', 16);
            $table->uuid('principal_id');
            $table->uuid('account_id');
            $table->timestamp('archived_at');
            $table->primary(['principal_type', 'principal_id']);
            $table->foreign('identity_id')->references('identity_id')->on('archived_identities');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_principals');
        Schema::dropIfExists('archived_accounts');
        Schema::dropIfExists('archived_identities');
    }
};
