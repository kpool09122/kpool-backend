<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array IMAGE_TABLES = ['draft_wiki_images', 'wiki_images', 'wiki_image_snapshots'];

    public function up(): void
    {
        foreach (self::IMAGE_TABLES as $tableName) {
            Schema::table($tableName, static function (Blueprint $table): void {
                $table->string('author', 255)->nullable()->comment('画像の作者');
                $table->string('license', 32)->nullable()->comment('画像のライセンス識別子');
            });
        }
    }

    public function down(): void
    {
        foreach (self::IMAGE_TABLES as $tableName) {
            Schema::table($tableName, static function (Blueprint $table): void {
                $table->dropColumn(['author', 'license']);
            });
        }
    }
};
