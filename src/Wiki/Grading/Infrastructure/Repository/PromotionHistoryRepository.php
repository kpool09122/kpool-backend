<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Infrastructure\Repository;

use Application\Models\Wiki\PromotionHistory as PromotionHistoryEloquent;
use Source\Wiki\Grading\Domain\Entity\PromotionHistory;
use Source\Wiki\Grading\Domain\Repository\PromotionHistoryRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\PromotionHistoryIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class PromotionHistoryRepository implements PromotionHistoryRepositoryInterface
{
    public function save(PromotionHistory $history): void
    {
        PromotionHistoryEloquent::query()->create([
            'id' => (string) $history->id(),
            'principal_id' => (string) $history->principalIdentifier(),
            'from_role' => $history->fromRole(),
            'to_role' => $history->toRole(),
            'reason' => $history->reason(),
            'processed_at' => $history->processedAt(),
        ]);
    }

    /**
     * @return PromotionHistory[]
     */
    public function findByPrincipal(PrincipalIdentifier $principalIdentifier): array
    {
        $eloquents = PromotionHistoryEloquent::query()
            ->where('principal_id', (string) $principalIdentifier)
            ->orderBy('processed_at', 'desc')
            ->get();

        return $eloquents->map(fn (PromotionHistoryEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    private function toDomainEntity(PromotionHistoryEloquent $eloquent): PromotionHistory
    {
        return new PromotionHistory(
            new PromotionHistoryIdentifier($eloquent->id),
            new PrincipalIdentifier($eloquent->principal_id),
            $eloquent->from_role,
            $eloquent->to_role,
            $eloquent->reason,
            $eloquent->processed_at->toDateTimeImmutable(),
        );
    }
}
