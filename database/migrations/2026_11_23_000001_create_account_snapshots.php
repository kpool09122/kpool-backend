<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('account_snapshots', function(Blueprint $table): void {
   $table->uuid('id')->primary();
   $table->foreignUuid('account_id')->constrained('accounts')->cascadeOnDelete();
   $table->unsignedInteger('version');
   $table->timestampTz('saved_at');
   $table->string('account_email'); $table->string('account_type'); $table->string('account_name'); $table->string('account_status'); $table->string('account_phone');
   $table->string('account_address_country_code'); $table->string('account_address_administrative_area_code')->nullable();
   $table->string('account_address_postal_code'); $table->string('account_address_locality')->nullable();
   $table->string('account_address_line1'); $table->string('account_address_line2')->nullable();
   $table->unique(['account_id','version']);
  });
  Schema::create('account_snapshot_documents', function(Blueprint $table): void {
   $table->id(); $table->foreignUuid('snapshot_id')->constrained('account_snapshots')->cascadeOnDelete();
   $table->string('document_type'); $table->string('document_path'); $table->timestampTz('uploaded_at'); $table->string('content_hash',64);
   $table->unique(['snapshot_id','document_type']);
  });
 }
 public function down(): void { Schema::dropIfExists('account_snapshot_documents'); Schema::dropIfExists('account_snapshots'); }
};
