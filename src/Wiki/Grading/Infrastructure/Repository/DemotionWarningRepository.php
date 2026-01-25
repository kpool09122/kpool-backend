<?php

declare(strict_types=1);

namespace Source\Wiki\Grading\Infrastructure\Repository;

use Application\Models\Wiki\DemotionWarning as DemotionWarningEloquent;
use DateTimeImmutable;
use Source\Wiki\Grading\Domain\Entity\DemotionWarning;
use Source\Wiki\Grading\Domain\Repository\DemotionWarningRepositoryInterface;
use Source\Wiki\Grading\Domain\ValueObject\DemotionWarningIdentifier;
use Source\Wiki\Grading\Domain\ValueObject\WarningCount;
use Source\Wiki\Grading\Domain\ValueObject\YearMonth;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class DemotionWarningRepository implements DemotionWarningRepositoryInterface
{
    public function save(DemotionWarning $warning): void
    {
        DemotionWarningEloquent::query()->updateOrCreate(
            ['principal_id' => (string) $warning->principalIdentifier()],
            [
                'id' => (string) $warning->id(),
                'warning_count' => $warning->warningCount()->value(),
                'last_warning_month' => (string) $warning->lastWarningMonth(),
            ]
        );
    }

    public function findByPrincipal(PrincipalIdentifier $principalIdentifier): ?DemotionWarning
    {
        $eloquent = DemotionWarningEloquent::query()
            ->where('principal_id', (string) $principalIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function delete(DemotionWarning $warning): void
    {
        DemotionWarningEloquent::query()
            ->where('id', (string) $warning->id())
            ->delete();
    }

    /**
     * @return DemotionWarning[]
     */
    public function findAll(): array
    {
        $eloquents = DemotionWarningEloquent::query()->get();

        return $eloquents->map(fn (DemotionWarningEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    private function toDomainEntity(DemotionWarningEloquent $eloquent): DemotionWarning
    {
        return new DemotionWarning(
            new DemotionWarningIdentifier($eloquent->id),
            new PrincipalIdentifier($eloquent->principal_id),
            new WarningCount($eloquent->warning_count),
            new YearMonth($eloquent->last_warning_month),
            $eloquent->created_at?->toDateTimeImmutable() ?? new DateTimeImmutable(),
            $eloquent->updated_at?->toDateTimeImmutable() ?? new DateTimeImmutable(),
        );
    }
}
