<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Domain\ValueObject;

use RuntimeException;

readonly class RegionalMetricObservation
{
    /** @param list<string> $quality */
    public function __construct(
        public string $country,
        public string $region,
        public string $metric,
        public string $value,
        public ?int $denominator = null,
        public array $quality = [],
        public string $wikiIdentifier = '',
        public string $translationSetIdentifier = '',
        public string $language = '',
    ) {
        if (preg_match('/^(?:0|[1-9][0-9]{0,19})(?:\.[0-9]{1,9})?$/D', $value) !== 1 || ($metric !== 'engagement_seconds' && str_contains($value, '.'))) {
            throw new RuntimeException('Invalid regional measurement.');
        }
        if ($denominator === 0 && preg_match('/^0(?:\.0+)?$/D', $value) !== 1) {
            throw new RuntimeException('An empty sample cannot contain positive engagement time.');
        }
    }
}
