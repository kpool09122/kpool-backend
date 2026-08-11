<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', static function (Blueprint $table): void {
            $table->string('phone', 32)->nullable()->after('category')->comment('Contact phone number');
            $table->string('address_country_code', 2)->nullable()->after('phone')->comment('Contact address country code');
            $table->string('address_administrative_area_code', 16)->nullable()->after('address_country_code')->comment('Contact address administrative area code');
            $table->string('address_postal_code', 16)->nullable()->after('address_administrative_area_code')->comment('Contact address postal code');
            $table->string('address_locality', 64)->nullable()->after('address_postal_code')->comment('Contact address locality');
            $table->string('address_line1', 252)->nullable()->after('address_locality')->comment('Contact address line 1');
            $table->string('address_line2', 252)->nullable()->after('address_line1')->comment('Contact address line 2');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', static function (Blueprint $table): void {
            $table->dropColumn([
                'phone',
                'address_country_code',
                'address_administrative_area_code',
                'address_postal_code',
                'address_locality',
                'address_line1',
                'address_line2',
            ]);
        });
    }
};
