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
        Schema::create('group_song', static function (Blueprint $table) {
            $table->uuid('group_id')->comment('グループID');
            $table->uuid('song_id')->comment('歌ID');
            $table->primary(['group_id', 'song_id']);
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('song_id')->references('id')->on('songs')->onDelete('cascade');
        });

        Schema::create('draft_group_song', static function (Blueprint $table) {
            $table->uuid('draft_group_id')->comment('下書きグループID');
            $table->uuid('song_id')->comment('歌ID');
            $table->primary(['draft_group_id', 'song_id']);
            $table->foreign('draft_group_id')->references('id')->on('draft_groups')->onDelete('cascade');
            $table->foreign('song_id')->references('id')->on('songs')->onDelete('cascade');
        });

        Schema::create('group_snapshot_song', static function (Blueprint $table) {
            $table->uuid('group_snapshot_id')->comment('グループスナップショットID');
            $table->uuid('song_id')->comment('歌ID');
            $table->primary(['group_snapshot_id', 'song_id']);
            $table->foreign('group_snapshot_id')->references('id')->on('group_snapshots')->onDelete('cascade');
            $table->foreign('song_id')->references('id')->on('songs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_snapshot_song');
        Schema::dropIfExists('draft_group_song');
        Schema::dropIfExists('group_song');
    }
};
