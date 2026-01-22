<?php

declare(strict_types=1);

namespace Tests\Helper;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Source\Wiki\Principal\Domain\ValueObject\PromotionHistoryIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class CreatePromotionHistory
{
    /**
     * @param array{
     *     from_role?: string,
     *     to_role?: string,
     *     reason?: ?string,
     *     processed_at?: DateTimeImmutable,
     * } $overrides
     */
    public static function create(
        PromotionHistoryIdentifier $id,
        PrincipalIdentifier $principalIdentifier,
        array $overrides = []
    ): void {
        DB::table('promotion_histories')->insert([
            'id' => (string) $id,
            'principal_id' => (string) $principalIdentifier,
            'from_role' => $overrides['from_role'] ?? 'GENERAL',
            'to_role' => $overrides['to_role'] ?? 'COLLABORATOR',
            'reason' => $overrides['reason'] ?? null,
            'processed_at' => $overrides['processed_at'] ?? now(),
        ]);
    }
}
