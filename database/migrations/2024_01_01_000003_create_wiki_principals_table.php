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
            $table->string('id', 26)->primary()->comment('プリンシパル ID');
            $table->string('identity_id', 26)->comment('Identity ID');
            $table->string('role', 32)->comment('ロール');
            $table->string('agency_id', 26)->nullable()->comment('事務所ID');
            $table->json('talent_ids')->comment('タレントID');
            $table->timestamps();

            $table->foreign('identity_id')->references('id')->on('identities')->onDelete('cascade');
            $table->unique('identity_id');
        });

        Schema::create('wiki_principal_groups', static function (Blueprint $table) {
            $table->string('wiki_principal_id', 26)->comment('プリンシパルID');
            $table->string('group_id', 26)->comment('グループID');

            $table->primary(['wiki_principal_id', 'group_id']);
            $table->foreign('wiki_principal_id')->references('id')->on('wiki_principals')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wiki_principal_groups');
        Schema::dropIfExists('wiki_principals');
    }
};
