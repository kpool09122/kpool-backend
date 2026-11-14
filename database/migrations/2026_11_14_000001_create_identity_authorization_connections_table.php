<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identity_authorization_connections', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('identity_id')->constrained('identities');
            $table->foreignUuid('client_id')->constrained('oauth_clients');
            $table->jsonb('scopes');
            $table->jsonb('targets');
            $table->uuid('original_account_id');
            $table->uuid('original_principal_id');
            $table->uuid('initial_account_id');
            $table->text('resource');
            $table->timestampTz('consented_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['identity_id', 'client_id']);
        });
        Schema::table('oauth_auth_codes', function (Blueprint $table): void {
            $table->foreignUuid('authorization_connection_id')->constrained('identity_authorization_connections');
        });
        Schema::table('oauth_access_tokens', function (Blueprint $table): void {
            $table->foreignUuid('authorization_connection_id')->constrained('identity_authorization_connections');
        });
    }

    public function down(): void
    {
        Schema::table('oauth_access_tokens', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('authorization_connection_id');
        });
        Schema::table('oauth_auth_codes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('authorization_connection_id');
        });
        Schema::dropIfExists('identity_authorization_connections');
    }
};
