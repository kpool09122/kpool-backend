<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wiki_video_links', static function (Blueprint $table): void {
            $table->dropColumn('video_usage');
        });
    }

    public function down(): void
    {
        Schema::table('wiki_video_links', static function (Blueprint $table): void {
            $table->string('video_usage', 32)->default('other')->comment('動画用途');
        });

        DB::table('wiki_video_links')
            ->whereNull('creator_principal_id')
            ->update(['video_usage' => 'youtube_auto_view_count']);

        Schema::table('wiki_video_links', static function (Blueprint $table): void {
            $table->string('video_usage', 32)->comment('動画用途')->change();
        });
    }
};
