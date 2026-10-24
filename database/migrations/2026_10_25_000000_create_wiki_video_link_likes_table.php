<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wiki_video_link_likes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('video_link_id')->constrained('wiki_video_links')->cascadeOnDelete();
            $table->foreignUuid('principal_id')->constrained('wiki_principals')->cascadeOnDelete();
            $table->timestamp('created_at');
            $table->unique(['video_link_id', 'principal_id']);
            $table->index(['principal_id', 'video_link_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_video_link_likes');
    }
};
