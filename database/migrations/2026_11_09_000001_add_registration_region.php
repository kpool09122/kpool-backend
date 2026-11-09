<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['identities', 'archived_identities'] as $name) {
            Schema::table($name, static function (Blueprint $table): void {
                $table->string('registration_region', 16)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['identities', 'archived_identities'] as $name) {
            Schema::table($name, static function (Blueprint $table): void {
                $table->dropColumn('registration_region');
            });
        }
    }
};
