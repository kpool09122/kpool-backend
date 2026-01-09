<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;

class CreatePrincipalGroupMembership
{
    public static function create(
        string $principalGroupId,
        string $principalId,
    ): void {
        DB::table('principal_group_memberships')->insert([
            'principal_group_id' => $principalGroupId,
            'principal_id' => $principalId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
