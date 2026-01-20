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
        Schema::create('invitations', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Invitation ID');
            $table->uuid('account_id')->comment('Account ID');
            $table->uuid('invited_by_identity_id')->comment('Inviter Identity ID');
            $table->string('email', 255)->comment('Invited Email Address');
            $table->string('token', 64)->unique()->comment('Invitation Token');
            $table->string('status', 20)->default('pending')->comment('Status (pending, accepted, revoked)');
            $table->timestamp('expires_at')->comment('Expiration Date');
            $table->uuid('accepted_by_identity_id')->nullable()->comment('Accepted Identity ID');
            $table->timestamp('accepted_at')->nullable()->comment('Accepted At');
            $table->timestamp('created_at')->comment('Created At');

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('invited_by_identity_id')->references('id')->on('identities')->onDelete('cascade');
            $table->foreign('accepted_by_identity_id')->references('id')->on('identities')->onDelete('set null');

            $table->index('email');
            $table->index('status');
            $table->index(['account_id', 'email', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
