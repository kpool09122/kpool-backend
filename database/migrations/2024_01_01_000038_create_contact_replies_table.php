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
        Schema::create('contact_replies', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('問い合わせ返信ID');
            $table->uuid('contact_id')->comment('問い合わせID');
            $table->text('content')->comment('返信内容');
            $table->text('to_email')->comment('送信先メールアドレス');
            $table->unsignedTinyInteger('status')->comment('送信ステータス（0:未送信, 1:送信済み, 2:送信失敗）');
            $table->uuid('identity_identifier')->nullable()->comment('返信者アイデンティティID');
            $table->dateTime('sent_at')->nullable()->comment('送信日時');
            $table->timestamps();

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->index(['contact_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_replies');
    }
};

