<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE accounts ALTER COLUMN type DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE accounts ALTER COLUMN type SET NOT NULL');
    }
};
