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
        Schema::create('talent_group', static function (Blueprint $table) {
            $table->uuid('talent_id')->comment('タレントID');
            $table->uuid('group_id')->comment('グループID');
            $table->primary(['talent_id', 'group_id']);
            $table->foreign('talent_id')->references('id')->on('talents')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });

        Schema::create('draft_talent_group', static function (Blueprint $table) {
            $table->uuid('draft_talent_id')->comment('下書きタレントID');
            $table->uuid('group_id')->comment('グループID');
            $table->primary(['draft_talent_id', 'group_id']);
            $table->foreign('draft_talent_id')->references('id')->on('draft_talents')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });

        Schema::create('talent_snapshot_group', static function (Blueprint $table) {
            $table->uuid('talent_snapshot_id')->comment('タレントスナップショットID');
            $table->uuid('group_id')->comment('グループID');
            $table->primary(['talent_snapshot_id', 'group_id']);
            $table->foreign('talent_snapshot_id')->references('id')->on('talent_snapshots')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talent_snapshot_group');
        Schema::dropIfExists('draft_talent_group');
        Schema::dropIfExists('talent_group');
    }
};
