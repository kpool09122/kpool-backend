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
        Schema::create('affiliation_grants', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('AffiliationGrant ID');
            $table->uuid('affiliation_id')->comment('Affiliation ID');
            $table->uuid('policy_id')->comment('Policy ID');
            $table->uuid('role_id')->comment('Role ID');
            $table->uuid('principal_group_id')->comment('PrincipalGroup ID');
            $table->string('type', 50)->comment('種別（talent_side / agency_side）');
            $table->timestamps();

            $table->index('affiliation_id');
            $table->index(['affiliation_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliation_grants');
    }
};
