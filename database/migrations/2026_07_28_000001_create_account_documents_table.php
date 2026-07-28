<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_documents', static function (Blueprint $table) {
            $table->uuid('account_id')->comment('Account ID');
            $table->string('document_type', 64)->comment('Document type');
            $table->string('document_path', 512)->comment('Document storage path');
            $table->timestamp('uploaded_at')->comment('Uploaded at');

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');

            $table->primary(['account_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_documents');
    }
};
