<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_passkey_credentials', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('identity_id')->index();
            $table->text('credential_id')->unique();
            $table->text('credential_source');
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->boolean('backup_eligible');
            $table->boolean('backup_state');
            $table->json('transports');
            $table->string('display_name', 100);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->foreign('identity_id')->references('id')->on('identities')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_passkey_credentials');
    }
};
