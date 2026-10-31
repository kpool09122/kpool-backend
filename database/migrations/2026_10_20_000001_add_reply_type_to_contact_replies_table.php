<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_replies', static function (Blueprint $table) {
            // 既存の返信履歴はすべて運営返信として保持する。
            // PostgreSQLではvarchar + CHECK制約となり、NULL・未定義の種別を許可しない。
            $table->enum('reply_type', ['operator', 'submitter'])
                ->default('operator')
                ->comment('返信種別（運営/問い合わせ本人）');
        });
    }

    public function down(): void
    {
        Schema::table('contact_replies', static function (Blueprint $table) {
            $table->dropColumn('reply_type');
        });
    }
};
