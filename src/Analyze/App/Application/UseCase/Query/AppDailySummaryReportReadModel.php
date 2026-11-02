<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\UseCase\Query;

use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;

/**
 * @phpstan-type DailyResult array{day: string, availability: string, value: ?string, quality: list<string>, updatedAt: ?string}
 */
readonly class AppDailySummaryReportReadModel
{
    /**
     * @param list<DailyResult> $days
     * @param ?string $total Closing balance for registered users; extended daily-user sum for application usage.
     */
    public function __construct(
        private MetricDefinition $definition,
        private ?string $revision,
        private array $days,
        private ?string $total,
    ) {
    }

    public function definition(): MetricDefinition
    {
        return $this->definition;
    }

    public function revision(): ?string
    {
        return $this->revision;
    }

    /** @return list<DailyResult> */
    public function days(): array
    {
        return $this->days;
    }

    public function total(): ?string
    {
        return $this->total;
    }
}
