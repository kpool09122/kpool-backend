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
        Schema::create('verification_documents', static function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Document ID');
            $table->uuid('verification_id')->comment('Verification ID');
            $table->string('document_type', 64)->comment('Document type');
            $table->string('document_path', 512)->comment('Document storage path');
            $table->string('original_file_name', 255)->comment('Original file name');
            $table->integer('file_size_bytes')->comment('File size in bytes');
            $table->timestamp('uploaded_at')->comment('Uploaded at');
            $table->timestamps();

            $table->foreign('verification_id')
                ->references('id')
                ->on('account_verifications')
                ->onDelete('cascade');

            $table->index('verification_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
    }
};
