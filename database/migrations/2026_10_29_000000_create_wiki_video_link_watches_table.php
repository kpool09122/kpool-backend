<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wiki_video_link_watches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('video_link_id')->constrained('wiki_video_links')->cascadeOnDelete();
            $table->uuid('owner_account_id');
            $table->uuid('principal_id');
            $table->timestamp('watched_at');
            $table->unique(['video_link_id', 'owner_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_video_link_watches');
    }
};
