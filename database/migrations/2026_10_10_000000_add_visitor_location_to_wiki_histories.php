<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wiki_histories', static function (Blueprint $table): void {
            $table->string('visitor_country', 2)->nullable();
            $table->string('visitor_region', 13)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wiki_histories', static function (Blueprint $table): void {
            $table->dropColumn(['visitor_country', 'visitor_region']);
        });
    }
};
