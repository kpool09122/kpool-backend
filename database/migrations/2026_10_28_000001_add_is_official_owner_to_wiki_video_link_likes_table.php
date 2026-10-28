<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wiki_video_link_likes', function (Blueprint $table): void {
            $table->boolean('is_official_owner')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('wiki_video_link_likes', function (Blueprint $table): void {
            $table->dropColumn('is_official_owner');
        });
    }
};
