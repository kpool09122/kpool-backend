<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_delegations', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Account Delegation ID');
            $table->uuid('affiliation_id')->index()->comment('Affiliation ID');
            $table->uuid('delegate_account_id')->index()->comment('Delegate Account ID');
            $table->uuid('delegator_account_id')->index()->comment('Delegator Account ID');
            $table->uuid('requested_by_account_id')->index()->comment('Requested by Account ID');
            $table->string('status', 32)->comment('Delegation status');
            $table->string('direction', 32)->comment('Delegation direction');
            $table->timestamp('requested_at')->comment('Requested at');
            $table->timestamp('approved_at')->nullable()->comment('Approved at');
            $table->timestamp('revoked_at')->nullable()->comment('Revoked at');
        });

        DB::statement("CREATE UNIQUE INDEX account_delegations_open_affiliation_unique ON account_delegations (affiliation_id) WHERE status IN ('pending', 'approved')");
    }

    public function down(): void
    {
        Schema::dropIfExists('account_delegations');
    }
};
