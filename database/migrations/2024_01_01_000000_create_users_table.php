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
        Schema::create('users', static function (Blueprint $table) {
            $table->string('id', 26)->primary()->comment('ユーザーID');
            $table->string('username', 32)->comment('ユーザー名');
            $table->string('email', 255)->unique()->comment('メールアドレス');
            $table->string('language', 8)->comment('言語設定');
            $table->string('profile_image', 2048)->nullable()->comment('プロフィール画像');
            $table->string('password', 255)->comment('パスワード');
            $table->timestamp('email_verified_at')->nullable()->comment('メール認証日時');
            $table->timestamps();
        });

        Schema::create('user_service_roles', static function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 26)->comment('ユーザーID');
            $table->string('service', 32)->comment('サービス名');
            $table->string('role', 32)->comment('ロール名');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'service', 'role']);
        });

        Schema::create('user_social_connections', static function (Blueprint $table) {
            $table->id();
            $table->string('user_id', 26)->comment('ユーザーID');
            $table->string('provider', 32)->comment('プロバイダ名');
            $table->string('provider_user_id', 255)->comment('プロバイダユーザーID');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['provider', 'provider_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_social_connections');
        Schema::dropIfExists('user_service_roles');
        Schema::dropIfExists('users');
    }
};
