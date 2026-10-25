<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('wiki_playlists', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('principal_id')->constrained('wiki_principals')->cascadeOnDelete();
            $table->string('name', 32);
            $table->timestamps();
            $table->index(['principal_id', 'created_at', 'id']);
        });
        Schema::create('wiki_playlist_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('playlist_id')->constrained('wiki_playlists')->cascadeOnDelete();
            $table->foreignUuid('video_link_id')->nullable()->constrained('wiki_video_links')->nullOnDelete();
            $table->string('provider', 32);
            $table->string('external_video_id', 255);
            $table->string('url', 255);
            $table->string('title', 255);
            $table->string('thumbnail_url', 255)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('position');
            $table->unique(['playlist_id', 'provider', 'external_video_id']);
            $table->index(['playlist_id', 'position', 'id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('wiki_playlist_items');
        Schema::dropIfExists('wiki_playlists');
    }
};
