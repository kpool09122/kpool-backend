<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Application\UseCase\Query;

/**
 * @phpstan-type ComparisonReason 'no_previous_data'|'no_current_data'|'zero_baseline'|'invalid_denominator'|'not_applicable'
 * @phpstan-type SerializedComparison array{from: string, to: string, percentageChange: ?string, reason: ?ComparisonReason, actorPercentageChange: ?string, actorReason: ?ComparisonReason}
 */
readonly class PeriodComparisonReadModel
{
    /**
     * @param ?ComparisonReason $reason
     * @param ?ComparisonReason $actorReason
     */
    public function __construct(
        private string $from,
        private string $to,
        private ?string $percentageChange,
        private ?string $reason,
        private ?string $actorPercentageChange,
        private ?string $actorReason,
    ) {
    }

    /** @return SerializedComparison */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'percentageChange' => $this->percentageChange,
            'reason' => $this->reason,
            'actorPercentageChange' => $this->actorPercentageChange,
            'actorReason' => $this->actorReason,
        ];
    }
}
