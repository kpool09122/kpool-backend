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
        Schema::create('principal_groups', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('PrincipalGroup ID');
            $table->uuid('account_id')->comment('Account ID');
            $table->string('name', 255)->comment('グループ名');
            $table->boolean('is_default')->default(false)->comment('Default PrincipalGroup かどうか');
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('principal_groups');
    }
};
