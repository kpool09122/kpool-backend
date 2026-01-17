<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Infrastructure\Repository;

use Application\Models\Monetization\SettlementSchedule as SettlementScheduleEloquent;
use DateTimeImmutable;
use Source\Monetization\Account\Domain\ValueObject\MonetizationAccountIdentifier;
use Source\Monetization\Settlement\Domain\Entity\SettlementSchedule;
use Source\Monetization\Settlement\Domain\Repository\SettlementScheduleRepositoryInterface;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementInterval;
use Source\Monetization\Settlement\Domain\ValueObject\SettlementScheduleIdentifier;
use Source\Shared\Domain\ValueObject\Currency;
use Source\Shared\Domain\ValueObject\Money;

class SettlementScheduleRepository implements SettlementScheduleRepositoryInterface
{
    public function findById(SettlementScheduleIdentifier $settlementScheduleIdentifier): ?SettlementSchedule
    {
        $eloquent = SettlementScheduleEloquent::query()
            ->where('id', (string) $settlementScheduleIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    public function findByMonetizationAccountId(MonetizationAccountIdentifier $monetizationAccountIdentifier): ?SettlementSchedule
    {
        $eloquent = SettlementScheduleEloquent::query()
            ->where('monetization_account_id', (string) $monetizationAccountIdentifier)
            ->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomainEntity($eloquent);
    }

    /**
     * @return array<SettlementSchedule>
     */
    public function findDueSchedules(DateTimeImmutable $date): array
    {
        $eloquents = SettlementScheduleEloquent::query()
            ->where('next_closing_date', '<=', $date->format('Y-m-d'))
            ->get();

        return $eloquents->map(fn (SettlementScheduleEloquent $eloquent) => $this->toDomainEntity($eloquent))->all();
    }

    public function save(SettlementSchedule $settlementSchedule): void
    {
        SettlementScheduleEloquent::query()->updateOrCreate(
            ['id' => (string) $settlementSchedule->settlementScheduleIdentifier()],
            [
                'monetization_account_id' => (string) $settlementSchedule->monetizationAccountIdentifier(),
                'interval' => $settlementSchedule->interval()->value,
                'payout_delay_days' => $settlementSchedule->paymentDelayDays(),
                'threshold_amount' => $settlementSchedule->threshold()?->amount(),
                'threshold_currency' => $settlementSchedule->threshold()?->currency()->value,
                'next_closing_date' => $settlementSchedule->nextClosingDate()->format('Y-m-d'),
            ]
        );
    }

    private function toDomainEntity(SettlementScheduleEloquent $eloquent): SettlementSchedule
    {
        $threshold = null;
        if ($eloquent->threshold_amount !== null && $eloquent->threshold_currency !== null) {
            $threshold = new Money($eloquent->threshold_amount, Currency::from($eloquent->threshold_currency));
        }

        return new SettlementSchedule(
            new SettlementScheduleIdentifier($eloquent->id),
            new MonetizationAccountIdentifier($eloquent->monetization_account_id),
            $eloquent->next_closing_date->toDateTimeImmutable(),
            SettlementInterval::from($eloquent->interval),
            $eloquent->payout_delay_days,
            $threshold
        );
    }
}
