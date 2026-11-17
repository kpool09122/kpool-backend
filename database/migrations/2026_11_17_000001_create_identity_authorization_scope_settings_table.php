<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('identity_authorization_scope_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('identity_id')->unique();
            $table->foreign('identity_id')->references('id')->on('identities')->cascadeOnDelete();
            $table->jsonb('scopes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identity_authorization_scope_settings');
    }
};
