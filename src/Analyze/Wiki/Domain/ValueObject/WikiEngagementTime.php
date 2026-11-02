<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;

/** Wiki・言語・国ごとの滞在秒数の合計と、平均算出に対応する母数。 */
readonly class WikiEngagementTime
{
    public function __construct(
        private string $totalSeconds,
        private ?int $sampleCount,
        private MetricProvenance $provenance,
    ) {
        if ($provenance->definition()->metric !== Metric::ENGAGEMENT_SECONDS) {
            throw new InvalidArgumentException('WikiEngagementTime requires its own metric definition.');
        }
        if (! preg_match('/^(?:0|[1-9][0-9]{0,19})(?:\.[0-9]{1,9})?$/D', $totalSeconds)) {
            throw new InvalidArgumentException('Engagement time requires nonnegative seconds with at most nine fractional digits.');
        }
        if ($sampleCount !== null && ($sampleCount < 0 || ($sampleCount === 0 && ! preg_match('/^0(?:\.0+)?$/D', $totalSeconds)))) {
            throw new InvalidArgumentException('An empty sample cannot contain positive engagement time.');
        }
    }

    public function totalSeconds(): string
    {
        return $this->totalSeconds;
    }

    public function sampleCount(): ?int
    {
        return $this->sampleCount;
    }

    public function provenance(): MetricProvenance
    {
        return $this->provenance;
    }
}
