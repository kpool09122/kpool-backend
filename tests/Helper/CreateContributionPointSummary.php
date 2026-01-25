<?php

declare(strict_types=1);

namespace Tests\Helper;

use Illuminate\Support\Facades\DB;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointSummaryIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class CreateContributionPointSummary
{
    /**
     * @param array{
     *     year_month?: string,
     *     points?: int,
     * } $overrides
     */
    public static function create(
        ContributionPointSummaryIdentifier $id,
        PrincipalIdentifier $principalIdentifier,
        array $overrides = []
    ): void {
        DB::table('contribution_point_summaries')->insert([
            'id' => (string) $id,
            'principal_id' => (string) $principalIdentifier,
            'year_month' => $overrides['year_month'] ?? '2024-01',
            'points' => $overrides['points'] ?? 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
