<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_replies', static function (Blueprint $table): void {
            $table->integer('reply_number')->comment('問い合わせ内の返信連番');
            $table->unique(['contact_id', 'reply_number']);
            $table->dropColumn('reply_type');
        });
        DB::statement('ALTER TABLE contact_replies ADD CONSTRAINT contact_replies_reply_number_positive CHECK (reply_number > 0)');
    }

    public function down(): void
    {
        Schema::table('contact_replies', static function (Blueprint $table): void {
            $table->enum('reply_type', ['operator', 'submitter'])->default('operator');
            $table->dropColumn('reply_number');
        });
    }
};
