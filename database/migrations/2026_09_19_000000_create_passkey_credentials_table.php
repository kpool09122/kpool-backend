<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passkey_credentials', static function (Blueprint $table): void {
            $table->uuid('id')->primary()->comment('パスキー資格情報ID');
            $table->uuid('identity_id')->index()->comment('Identity ID');
            $table->text('credential_id')->unique()->comment('WebAuthn credential ID (base64url)');
            $table->jsonb('credential_source')->comment('WebAuthn credential record');
            $table->unsignedBigInteger('sign_count')->default(0)->comment('署名カウンター');
            $table->boolean('backup_eligible')->comment('Backup Eligibility (BE)');
            $table->boolean('backup_state')->comment('Backup State (BS)');
            $table->jsonb('transports')->default('[]')->comment('Authenticator transports');
            $table->string('display_name', 64)->comment('表示名');
            $table->timestamp('last_used_at')->nullable()->comment('最終利用日時');
            $table->timestamps();

            $table->foreign('identity_id')->references('id')->on('identities')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkey_credentials');
    }
};
