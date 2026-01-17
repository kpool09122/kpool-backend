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
        Schema::create('contacts', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('問い合わせID');
            $table->unsignedTinyInteger('category')->comment('カテゴリ');
            $table->string('name', 32)->comment('氏名');
            $table->string('email', 255)->comment('メールアドレス');
            $table->string('content', 512)->comment('問い合わせ内容');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};

