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
        Schema::create('song_talent', static function (Blueprint $table) {
            $table->uuid('song_id')->comment('歌ID');
            $table->uuid('talent_id')->comment('タレントID');
            $table->primary(['song_id', 'talent_id']);
            $table->foreign('song_id')->references('id')->on('songs')->onDelete('cascade');
            $table->foreign('talent_id')->references('id')->on('talents')->onDelete('cascade');
        });

        Schema::create('draft_song_talent', static function (Blueprint $table) {
            $table->uuid('draft_song_id')->comment('下書き歌ID');
            $table->uuid('talent_id')->comment('タレントID');
            $table->primary(['draft_song_id', 'talent_id']);
            $table->foreign('draft_song_id')->references('id')->on('draft_songs')->onDelete('cascade');
            $table->foreign('talent_id')->references('id')->on('talents')->onDelete('cascade');
        });

        Schema::create('song_snapshot_talent', static function (Blueprint $table) {
            $table->uuid('song_snapshot_id')->comment('歌スナップショットID');
            $table->uuid('talent_id')->comment('タレントID');
            $table->primary(['song_snapshot_id', 'talent_id']);
            $table->foreign('song_snapshot_id')->references('id')->on('song_snapshots')->onDelete('cascade');
            $table->foreign('talent_id')->references('id')->on('talents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_snapshot_talent');
        Schema::dropIfExists('draft_song_talent');
        Schema::dropIfExists('song_talent');
    }
};
