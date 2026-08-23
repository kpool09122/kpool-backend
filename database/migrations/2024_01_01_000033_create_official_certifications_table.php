<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('official_certifications', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Official certification ID');
            $table->string('resource_type', 32)->comment('Resource type');
            $table->uuid('translation_set_identifier')->comment('Translation set identifier');
            $table->uuid('owner_account_id')->comment('Owner account ID');
            $table->string('status', 32)->comment('Certification status');
            $table->timestamp('requested_at')->comment('Requested at');
            $table->timestamp('approved_at')->nullable()->comment('Approved at');
            $table->timestamp('rejected_at')->nullable()->comment('Rejected at');
            $table->timestamps();

        });

        DB::statement("CREATE UNIQUE INDEX official_certifications_pending_resource_unique ON official_certifications (resource_type, translation_set_identifier) WHERE status = 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS official_certifications_pending_resource_unique');

        Schema::dropIfExists('official_certifications');
    }
};
