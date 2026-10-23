<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('video_links', 'wiki_video_links');

        Schema::table('wiki_video_links', function (Blueprint $table): void {
            $table->uuid('creator_principal_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wiki_video_links', function (Blueprint $table): void {
            $table->dropColumn('creator_principal_id');
        });

        Schema::rename('wiki_video_links', 'video_links');
    }
};
