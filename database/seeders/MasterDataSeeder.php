<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                SystemPolicySeeder::class,
                SystemRoleSeeder::class,
                AccountAuthorizationSeeder::class,
                SiteManagementAuthorizationSeeder::class,
            ]);
        });
    }
}
