<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', static function (Blueprint $table): void {
            $table->string('phone', 32)->nullable()->after('category')->comment('Contact phone number');
            $table->json('address')->nullable()->after('phone')->comment('Contact address JSON');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', static function (Blueprint $table): void {
            $table->dropColumn(['phone', 'address']);
        });
    }
};
