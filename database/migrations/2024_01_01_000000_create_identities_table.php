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
        Schema::create('identities', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('アイデンティティID');
            $table->string('username', 32)->comment('ユーザー名');
            $table->string('email', 255)->unique()->comment('メールアドレス');
            $table->string('language', 8)->comment('言語設定');
            $table->string('profile_image', 2048)->nullable()->comment('プロフィール画像');
            $table->string('password', 255)->comment('パスワード');
            $table->timestamp('email_verified_at')->nullable()->comment('メール認証日時');
            $table->timestamps();
        });

        Schema::create('identity_social_connections', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('ソーシャルコネクションID');
            $table->uuid('identity_id')->comment('アイデンティティID');
            $table->string('provider', 32)->comment('プロバイダ名');
            $table->string('provider_user_id', 255)->comment('プロバイダユーザーID');
            $table->timestamps();

            $table->foreign('identity_id')->references('id')->on('identities')->onDelete('cascade');
            $table->unique(['provider', 'provider_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('identity_social_connections');
        Schema::dropIfExists('identities');
    }
};
