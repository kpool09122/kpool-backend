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
        Schema::create('wiki_policies', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Policy ID');
            $table->uuid('account_id')->nullable()->index()->comment('Account ID（null は system/global）');
            $table->string('name', 255)->comment('ポリシー名');
            $table->json('statements')->comment('Statement の配列（JSON）');
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX wiki_policies_system_name_unique ON wiki_policies (name) WHERE account_id IS NULL');
        DB::statement('CREATE UNIQUE INDEX wiki_policies_account_name_unique ON wiki_policies (account_id, name) WHERE account_id IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_policies');
    }
};
