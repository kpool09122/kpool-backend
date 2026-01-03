<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreateAccount
{
    /**
     * @param array{
     *     email?: string,
     *     type?: string,
     *     name?: string,
     *     status?: string,
     *     contract_info?: string,
     * } $overrides
     */
    public static function create(string $accountId, array $overrides = []): void
    {
        DB::table('accounts')->insert([
            'id' => $accountId,
            'email' => $overrides['email'] ?? 'test-' . $accountId . '@example.com',
            'type' => $overrides['type'] ?? 'individual',
            'name' => $overrides['name'] ?? 'Test Account',
            'status' => $overrides['status'] ?? 'active',
            'contract_info' => $overrides['contract_info'] ?? '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
