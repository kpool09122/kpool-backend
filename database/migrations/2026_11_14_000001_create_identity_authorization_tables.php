<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->nullableUuidMorphs('owner');
            $table->string('name');
            $table->string('secret')->nullable();
            $table->string('provider')->nullable();
            $table->text('redirect_uris');
            $table->text('grant_types');
            $table->boolean('revoked');
            $table->timestamps();
        });

        Schema::create('identity_authorization_consents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('identity_id')->constrained('identities');
            $table->foreignUuid('client_id')->constrained('oauth_clients');
            $table->jsonb('scopes');
            $table->uuid('original_account_id');
            $table->uuid('original_principal_id');
            $table->text('resource');
            $table->timestampTz('consented_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['identity_id', 'client_id']);
        });

        Schema::create('oauth_auth_codes', function (Blueprint $table): void {
            $table->char('id', 80)->primary();
            $table->uuid('user_id')->index();
            $table->foreignUuid('client_id');
            $table->text('scopes')->nullable();
            $table->boolean('revoked');
            $table->dateTime('expires_at')->nullable();
            $table->foreignUuid('authorization_consent_id')->constrained('identity_authorization_consents');
        });

        Schema::create('oauth_access_tokens', function (Blueprint $table): void {
            $table->char('id', 80)->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->foreignUuid('client_id');
            $table->string('name')->nullable();
            $table->text('scopes')->nullable();
            $table->boolean('revoked');
            $table->timestamps();
            $table->dateTime('expires_at')->nullable();
            $table->foreignUuid('authorization_consent_id')->constrained('identity_authorization_consents');
        });

        Schema::create('oauth_refresh_tokens', function (Blueprint $table): void {
            $table->char('id', 80)->primary();
            $table->char('access_token_id', 80)->index();
            $table->boolean('revoked');
            $table->dateTime('expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_refresh_tokens');
        Schema::dropIfExists('oauth_access_tokens');
        Schema::dropIfExists('oauth_auth_codes');
        Schema::dropIfExists('identity_authorization_consents');
        Schema::dropIfExists('oauth_clients');
    }
};
