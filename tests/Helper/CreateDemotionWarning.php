<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Principal\Domain\ValueObject\DemotionWarningIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class CreateDemotionWarning
{
    /**
     * @param array{
     *     warning_count?: int,
     *     last_warning_month?: string,
     * } $overrides
     */
    public static function create(
        DemotionWarningIdentifier $id,
        PrincipalIdentifier $principalIdentifier,
        array $overrides = []
    ): void {
        DB::table('demotion_warnings')->insert([
            'id' => (string) $id,
            'principal_id' => (string) $principalIdentifier,
            'warning_count' => $overrides['warning_count'] ?? 1,
            'last_warning_month' => $overrides['last_warning_month'] ?? '2024-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
