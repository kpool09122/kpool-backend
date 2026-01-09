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
        Schema::create('principal_group_memberships', static function (Blueprint $table) {
            $table->uuid('principal_group_id')->comment('PrincipalGroup ID');
            $table->uuid('principal_id')->comment('Principal ID');
            $table->timestamps();

            $table->primary(['principal_group_id', 'principal_id']);
            $table->foreign('principal_group_id')->references('id')->on('principal_groups')->onDelete('cascade');
            $table->foreign('principal_id')->references('id')->on('wiki_principals')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('principal_group_memberships');
    }
};
