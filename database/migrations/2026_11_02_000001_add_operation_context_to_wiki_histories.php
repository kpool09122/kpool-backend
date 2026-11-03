<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wiki_histories', static function (Blueprint $table): void {
            $table->uuid('translation_set_identifier')->nullable();
            $table->string('language', 8)->nullable();
            $table->string('resource_type', 50)->nullable();
            $table->uuid('actor_identity_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wiki_histories', static function (Blueprint $table): void {
            $table->dropColumn(['translation_set_identifier', 'language', 'resource_type', 'actor_identity_id']);
        });
    }
};
