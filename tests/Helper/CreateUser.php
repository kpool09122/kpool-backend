<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;
use Source\SiteManagement\User\Domain\ValueObject\Role;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

class CreateUser
{
    /**
     * @param array{
     *     role?: Role
     * } $overrides
     */
    public static function create(
        UserIdentifier $userIdentifier,
        IdentityIdentifier $identityIdentifier,
        array $overrides = []
    ): void {
        DB::table('site_management_users')->insert([
            'id' => (string) $userIdentifier,
            'identity_id' => (string) $identityIdentifier,
            'role' => ($overrides['role'] ?? Role::NONE)->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
