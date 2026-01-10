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
            $table->uuid('id')->primary()->comment('プリンシパル ID');
            $table->uuid('identity_id')->comment('Identity ID');
            $table->uuid('agency_id')->nullable()->comment('事務所ID');
            $table->json('talent_ids')->comment('タレントID');
            $table->uuid('delegation_identifier')->nullable()->comment('委譲ID (nullなら本人)');
            $table->boolean('enabled')->default(true)->comment('有効フラグ');
            $table->timestamps();

            $table->foreign('identity_id')->references('id')->on('identities')->onDelete('cascade');
            $table->unique('identity_id');
            $table->unique('delegation_identifier');
        });

        Schema::create('wiki_principal_groups', static function (Blueprint $table) {
            $table->uuid('wiki_principal_id')->comment('プリンシパルID');
            $table->uuid('group_id')->comment('グループID');

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
