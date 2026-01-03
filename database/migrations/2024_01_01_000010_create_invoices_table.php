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
        Schema::create('invoices', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('請求書ID');
            $table->uuid('order_id')->comment('注文ID');
            $table->uuid('customer_id')->comment('顧客ID');
            $table->string('currency', 3)->comment('通貨コード');
            $table->unsignedBigInteger('subtotal')->comment('小計');
            $table->unsignedBigInteger('discount_amount')->comment('割引額');
            $table->unsignedBigInteger('tax_amount')->comment('税額');
            $table->unsignedBigInteger('total')->comment('合計');
            $table->timestamp('issued_at')->comment('発行日時');
            $table->timestamp('due_date')->comment('支払期限');
            $table->string('status', 32)->comment('ステータス');
            $table->string('tax_document_type', 64)->nullable()->comment('税書類タイプ');
            $table->string('tax_document_country', 2)->nullable()->comment('税書類国コード');
            $table->string('tax_document_registration_number', 255)->nullable()->comment('登録番号');
            $table->timestamp('tax_document_issue_deadline')->nullable()->comment('税書類発行期限');
            $table->text('tax_document_reason')->nullable()->comment('税書類理由');
            $table->timestamp('paid_at')->nullable()->comment('支払日時');
            $table->timestamp('voided_at')->nullable()->comment('失効日時');
            $table->text('void_reason')->nullable()->comment('失効理由');

            $table->index('order_id');
            $table->index('customer_id');
            $table->index('status');
        });

        Schema::create('invoice_lines', static function (Blueprint $table) {
            $table->id()->comment('明細ID');
            $table->uuid('invoice_id')->comment('請求書ID');
            $table->string('description', 255)->comment('説明');
            $table->string('currency', 3)->comment('通貨コード');
            $table->unsignedBigInteger('unit_price')->comment('単価');
            $table->unsignedInteger('quantity')->comment('数量');
            $table->timestamp('created_at')->useCurrent()->comment('作成日時');

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
