<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('principal_group_role_attachments', function (Blueprint $table) {
            $table->uuid('principal_group_id');
            $table->uuid('role_id');

            $table->primary(['principal_group_id', 'role_id']);

            $table->foreign('principal_group_id')
                ->references('id')
                ->on('principal_groups')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('principal_group_role_attachments');
    }
};
