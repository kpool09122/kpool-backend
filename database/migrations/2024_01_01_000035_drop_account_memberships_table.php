<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('account_memberships');
    }

    public function down(): void
    {
        Schema::create('account_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('identity_id');
            $table->string('role');
            $table->timestamps();

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->cascadeOnDelete();

            $table->foreign('identity_id')
                ->references('id')
                ->on('identities')
                ->cascadeOnDelete();

            $table->unique(['account_id', 'identity_id']);
        });
    }
};
