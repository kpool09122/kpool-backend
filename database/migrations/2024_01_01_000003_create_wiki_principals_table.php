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
        Schema::create('wiki_principals', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('Principal ID');
            $table->string('identity_id', 26)->comment('Identity ID');
            $table->string('role', 32)->comment('Role');
            $table->string('agency_id', 26)->nullable()->comment('Agency ID');
            $table->json('group_ids')->comment('Group IDs');
            $table->json('talent_ids')->comment('Talent IDs');
            $table->timestamps();

            $table->foreign('identity_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('identity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_principals');
    }
};
