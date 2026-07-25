<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts', static function (Blueprint $table) {
            $table->string('language', 8)
                ->default('en')
                ->comment('問い合わせ時の言語');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', static function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
