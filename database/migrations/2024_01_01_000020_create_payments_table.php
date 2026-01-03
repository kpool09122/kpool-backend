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
        Schema::create('payments', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('決済ID');
            $table->uuid('order_id')->comment('注文ID');
            $table->uuid('buyer_monetization_account_id')->comment('購入者MonetizationアカウントID');
            $table->string('currency', 3)->comment('通貨コード');
            $table->unsignedBigInteger('amount')->comment('決済金額');
            $table->uuid('payment_method_id')->comment('決済手段ID');
            $table->string('payment_method_type', 32)->comment('決済手段タイプ');
            $table->string('payment_method_label', 255)->comment('決済手段ラベル');
            $table->boolean('payment_method_recurring_enabled')->comment('継続課金有効フラグ');
            $table->timestamp('created_at')->comment('作成日時');
            $table->string('status', 32)->comment('ステータス');
            $table->timestamp('authorized_at')->nullable()->comment('オーソリ日時');
            $table->timestamp('captured_at')->nullable()->comment('売上確定日時');
            $table->timestamp('failed_at')->nullable()->comment('失敗日時');
            $table->text('failure_reason')->nullable()->comment('失敗理由');
            $table->unsignedBigInteger('refunded_amount')->default(0)->comment('返金済み金額');
            $table->timestamp('last_refunded_at')->nullable()->comment('最終返金日時');
            $table->text('last_refund_reason')->nullable()->comment('最終返金理由');

            $table->index('order_id');
            $table->index('buyer_monetization_account_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
