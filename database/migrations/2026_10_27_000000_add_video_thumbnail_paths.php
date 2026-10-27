<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wiki_video_thumbnails', static function (Blueprint $table): void {
            $table->string('path', 255)->primary();
        });
        foreach (['wiki_video_links', 'wiki_playlist_items'] as $name) {
            Schema::table($name, static function (Blueprint $table): void {
                $table->string('thumbnail_path', 255)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_video_thumbnails');
        foreach (['wiki_playlist_items', 'wiki_video_links'] as $name) {
            Schema::table($name, static function (Blueprint $table): void {
                $table->dropColumn('thumbnail_path');
            });
        }
    }
};
