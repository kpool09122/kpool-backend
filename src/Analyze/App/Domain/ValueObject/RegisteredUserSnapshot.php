<?php

declare(strict_types=1);

namespace Source\Analyze\App\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricProvenance;

/** UTC日末時点の登録者残高。期間集計には最終日の残高を用いる。 */
readonly class RegisteredUserSnapshot
{
    public function __construct(
        private string $count,
        private DateTimeImmutable $asOf,
        private MetricProvenance $provenance,
    ) {
        if ($provenance->definition()->metric !== Metric::REGISTERED_USERS) {
            throw new InvalidArgumentException('RegisteredUserSnapshot requires its own metric definition.');
        }
        if (! preg_match('/^(?:0|[1-9][0-9]{0,19})$/D', $count)) {
            throw new InvalidArgumentException('RegisteredUserSnapshot requires a nonnegative integer of at most twenty digits.');
        }
        if ($asOf != $provenance->day()->next()->instant()) {
            throw new InvalidArgumentException('Registered users require the UTC closing-balance timestamp.');
        }
    }

    public function count(): string
    {
        return $this->count;
    }

    public function asOf(): DateTimeImmutable
    {
        return $this->asOf;
    }

    public function provenance(): MetricProvenance
    {
        return $this->provenance;
    }
}
