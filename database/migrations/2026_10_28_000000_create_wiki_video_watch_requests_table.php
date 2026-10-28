<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wiki_video_watch_requests', static function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // Keep history after a video is deleted, for downstream notifications.
            $table->uuid('video_link_id');
            $table->uuid('wiki_id');
            $table->uuid('principal_id');
            $table->timestampTz('sent_at');
            $table->index(['wiki_id', 'principal_id', 'sent_at'], 'watch_requests_sender_window');
            $table->index(['video_link_id', 'sent_at'], 'watch_requests_video_window');
            $table->index(['video_link_id', 'principal_id', 'sent_at'], 'watch_requests_resend_window');
            $table->index('sent_at');
        });
    }
    public function down(): void { Schema::dropIfExists('wiki_video_watch_requests'); }
};
