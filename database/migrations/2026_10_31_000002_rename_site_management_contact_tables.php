<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('contacts', 'site_management_contacts');
        Schema::rename('contact_replies', 'site_management_contact_replies');
    }

    public function down(): void
    {
        Schema::rename('site_management_contact_replies', 'contact_replies');
        Schema::rename('site_management_contacts', 'contacts');
    }
};
