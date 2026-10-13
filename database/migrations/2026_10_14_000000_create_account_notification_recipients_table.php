<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_notification_recipients', function (Blueprint $table): void {
            $table->foreignUuid('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignUuid('identity_id')->constrained('identities')->cascadeOnDelete();
            $table->primary(['account_id', 'identity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_notification_recipients');
    }
};
