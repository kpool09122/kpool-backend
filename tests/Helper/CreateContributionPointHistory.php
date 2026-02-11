<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Wiki\Grading\Domain\ValueObject\ContributionPointHistoryIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class CreateContributionPointHistory
{
    /**
     * @param array{
     *     year_month?: string,
     *     points?: int,
     *     resource_type?: string,
     *     wiki_id?: string,
     *     contributor_type?: string,
     *     is_new_creation?: bool,
     *     created_at?: DateTimeImmutable,
     * } $overrides
     */
    public static function create(
        ContributionPointHistoryIdentifier $id,
        PrincipalIdentifier $principalIdentifier,
        array $overrides = []
    ): void {
        DB::table('contribution_point_histories')->insert([
            'id' => (string) $id,
            'principal_id' => (string) $principalIdentifier,
            'year_month' => $overrides['year_month'] ?? '2024-01',
            'points' => $overrides['points'] ?? 10,
            'resource_type' => $overrides['resource_type'] ?? 'talent',
            'wiki_id' => $overrides['wiki_id'] ?? StrTestHelper::generateUuid(),
            'contributor_type' => $overrides['contributor_type'] ?? 'editor',
            'is_new_creation' => $overrides['is_new_creation'] ?? true,
            'created_at' => $overrides['created_at'] ?? now(),
        ]);
    }
}
