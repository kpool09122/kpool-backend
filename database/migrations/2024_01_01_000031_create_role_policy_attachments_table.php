<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('role_policy_attachments', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('policy_id');

            $table->primary(['role_id', 'policy_id']);

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('policy_id')
                ->references('id')
                ->on('policies')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_policy_attachments');
    }
};
