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
        Schema::create('account_affiliations', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Affiliation ID');
            $table->uuid('agency_account_id')->comment('Agency Account ID');
            $table->uuid('talent_account_id')->comment('Talent Account ID');
            $table->uuid('requested_by')->comment('Requested by Account ID');
            $table->string('status', 32)->comment('Affiliation status');
            $table->unsignedTinyInteger('revenue_share_percentage')->nullable()->comment('Revenue share percentage');
            $table->text('contract_notes')->nullable()->comment('Contract notes');
            $table->timestamp('requested_at')->comment('Requested at');
            $table->timestamp('activated_at')->nullable()->comment('Activated at');
            $table->timestamp('terminated_at')->nullable()->comment('Terminated at');

            $table->index(['agency_account_id', 'status']);
            $table->index(['talent_account_id', 'status']);
            $table->index(['agency_account_id', 'talent_account_id', 'status']);
            $table->index(['requested_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_affiliations');
    }
};
