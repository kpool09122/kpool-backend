<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;

/** Wiki・言語・国ごとの日次閲覧者数。期間・対象間では延べ加算する。 */
readonly class WikiViewerCount
{
    public function __construct(
        private string $count,
        private MetricProvenance $provenance,
    ) {
        if ($provenance->definition()->metric !== Metric::VIEWERS) {
            throw new InvalidArgumentException('WikiViewerCount requires its own metric definition.');
        }
        if (! preg_match('/^(?:0|[1-9][0-9]{0,19})$/D', $count)) {
            throw new InvalidArgumentException('WikiViewerCount requires a nonnegative integer of at most twenty digits.');
        }
    }

    public function count(): string
    {
        return $this->count;
    }

    public function provenance(): MetricProvenance
    {
        return $this->provenance;
    }
}
