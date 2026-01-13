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
        Schema::create('account_verifications', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Verification ID');
            $table->uuid('account_id')->comment('Account ID');
            $table->string('verification_type', 32)->comment('Verification type: talent or agency');
            $table->string('status', 32)->default('pending')->comment('Status: pending, under_review, approved, rejected');
            $table->json('applicant_info')->comment('Applicant information');
            $table->timestamp('requested_at')->comment('Requested at');
            $table->uuid('reviewed_by')->nullable()->comment('Reviewer account ID');
            $table->timestamp('reviewed_at')->nullable()->comment('Reviewed at');
            $table->json('rejection_reason')->nullable()->comment('Rejection reason');
            $table->timestamps();

            $table->index('account_id');
            $table->index('status');
            $table->index(['account_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_verifications');
    }
};
