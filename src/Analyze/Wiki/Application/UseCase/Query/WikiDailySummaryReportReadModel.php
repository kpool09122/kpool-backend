<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Application\UseCase\Query;

use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;

/**
 * @phpstan-type DailyResult array{day: string, availability: string, value: ?string, denominator: ?int, quality: list<string>, updatedAt: ?string}
 */
readonly class WikiDailySummaryReportReadModel
{
    /**
     * @param list<DailyResult> $days
     * @param ?string $total Additive total over the selected daily Wiki summaries, including extended viewers.
     */
    public function __construct(
        private MetricDefinition $definition,
        private ?string $revision,
        private array $days,
        private ?string $total,
        private ?int $denominator,
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

    public function denominator(): ?int
    {
        return $this->denominator;
    }
}
