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
        Schema::create('song_group', static function (Blueprint $table) {
            $table->uuid('song_id')->comment('歌ID');
            $table->uuid('group_id')->comment('グループID');
            $table->primary(['song_id', 'group_id']);
            $table->foreign('song_id')->references('id')->on('songs')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });

        Schema::create('draft_song_group', static function (Blueprint $table) {
            $table->uuid('draft_song_id')->comment('下書き歌ID');
            $table->uuid('group_id')->comment('グループID');
            $table->primary(['draft_song_id', 'group_id']);
            $table->foreign('draft_song_id')->references('id')->on('draft_songs')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });

        Schema::create('song_snapshot_group', static function (Blueprint $table) {
            $table->uuid('song_snapshot_id')->comment('歌スナップショットID');
            $table->uuid('group_id')->comment('グループID');
            $table->primary(['song_snapshot_id', 'group_id']);
            $table->foreign('song_snapshot_id')->references('id')->on('song_snapshots')->onDelete('cascade');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_snapshot_group');
        Schema::dropIfExists('draft_song_group');
        Schema::dropIfExists('song_group');
    }
};
