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
        Schema::create('site_management_users', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('ユーザーID');
            $table->uuid('identity_id')->comment('アイデンティティID');
            $table->string('role', 32)->comment('ロール');
            $table->timestamps();

            $table->foreign('identity_id')->references('id')->on('identities')->onDelete('cascade');
            $table->unique('identity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_management_users');
    }
};
