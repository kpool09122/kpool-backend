<?php

declare(strict_types=1);

namespace Source\Analyze\App\Domain\ValueObject;

use InvalidArgumentException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;

/** UTC一日のアプリ全体の利用者数。期間集計では日ごとの人数を延べ加算する。 */
readonly class ApplicationUserCount
{
    public function __construct(
        private string $count,
        private MetricProvenance $provenance,
    ) {
        if ($provenance->definition()->metric !== Metric::APPLICATION_USERS) {
            throw new InvalidArgumentException('ApplicationUserCount requires its own metric definition.');
        }
        if (! preg_match('/^(?:0|[1-9][0-9]{0,19})$/D', $count)) {
            throw new InvalidArgumentException('ApplicationUserCount requires a nonnegative integer of at most twenty digits.');
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
