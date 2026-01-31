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
        Schema::create('image_hide_requests', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('リクエストID');
            $table->uuid('image_id')->comment('対象画像ID');
            $table->string('requester_name', 255)->comment('申請者名');
            $table->string('requester_email', 255)->comment('申請者メールアドレス');
            $table->text('reason')->comment('非表示理由');
            $table->string('status', 32)->comment('ステータス (pending, approved, rejected)');
            $table->timestamp('requested_at')->comment('申請日時');
            $table->uuid('reviewer_id')->nullable()->comment('審査者ID');
            $table->timestamp('reviewed_at')->nullable()->comment('審査日時');
            $table->text('reviewer_comment')->nullable()->comment('審査コメント');
            $table->timestamps();

            $table->index('image_id', 'idx_image_id');
            $table->index('status', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('image_hide_requests');
    }
};
