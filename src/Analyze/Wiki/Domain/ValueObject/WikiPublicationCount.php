<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;

/** Wiki・言語・国ごとの日次公開件数。 */
readonly class WikiPublicationCount
{
    public function __construct(
        private string $count,
        private MetricProvenance $provenance,
    ) {
        if ($provenance->definition()->metric !== Metric::PUBLICATIONS) {
            throw new InvalidArgumentException('WikiPublicationCount requires its own metric definition.');
        }
        if (! preg_match('/^(?:0|[1-9][0-9]{0,19})$/D', $count)) {
            throw new InvalidArgumentException('WikiPublicationCount requires a nonnegative integer of at most twenty digits.');
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
