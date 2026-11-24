<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('account_category_change_requests', static function (Blueprint $table): void {
            $table->foreignUuid('snapshot_id')->constrained('account_snapshots')->noActionOnDelete();
            $table->foreignUuid('approved_snapshot_id')->nullable()->constrained('account_snapshots')->noActionOnDelete();
            $table->dropColumn(['account_email', 'account_type', 'account_name', 'account_status', 'account_phone', 'account_address_country_code', 'account_address_administrative_area_code', 'account_address_postal_code', 'account_address_locality', 'account_address_line1', 'account_address_line2']);
        });
        Schema::drop('account_category_change_request_documents');
    }

    public function down(): void
    {
        Schema::table('account_category_change_requests', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('snapshot_id');
            $table->dropConstrainedForeignId('approved_snapshot_id');
            $table->string('account_email');
            $table->string('account_type');
            $table->string('account_name');
            $table->string('account_status');
            $table->string('account_phone', 32);
            $table->string('account_address_country_code', 2);
            $table->string('account_address_administrative_area_code', 16)->nullable();
            $table->string('account_address_postal_code', 16);
            $table->string('account_address_locality', 64)->nullable();
            $table->string('account_address_line1', 252);
            $table->string('account_address_line2', 252)->nullable();
        });
        Schema::create('account_category_change_request_documents', static function (Blueprint $table): void {
            $table->uuid('request_id');
            $table->string('document_type');
            $table->string('document_path');
            $table->timestamp('uploaded_at');
            $table->primary(['request_id', 'document_type']);
            $table->foreign('request_id')->references('id')->on('account_category_change_requests')->cascadeOnDelete();
        });
    }
};
