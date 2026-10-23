<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('video_links', static function (Blueprint $table): void {
            $table->unsignedInteger('duration_seconds')->nullable()->comment('動画の長さ（秒）');
        });
    }

    public function down(): void
    {
        Schema::table('video_links', static function (Blueprint $table): void {
            $table->dropColumn('duration_seconds');
        });
    }
};
