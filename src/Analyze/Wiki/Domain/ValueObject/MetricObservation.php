<?php

declare(strict_types=1);

namespace Source\Analyze\Wiki\Domain\ValueObject;

use InvalidArgumentException;
use Source\Analyze\Shared\Domain\ValueObject\Metric;
use Source\Analyze\Shared\Domain\ValueObject\MetricDefinition;
use Source\Analyze\Shared\Domain\ValueObject\UtcDate;

readonly class MetricObservation
{
    public function __construct(
        public string $targetIdentifier,
        public string $translationSetIdentifier,
        public string $language,
        public string $country,
        public string $value,
        public ?int $denominator = null,
    ) {
        foreach ([$targetIdentifier, $translationSetIdentifier] as $key) {
            if (strlen($key) > 128 || preg_match('/[\x00-\x1F\x7F]/', $key)) {
                throw new InvalidArgumentException('Invalid metric target.');
            }
        }
        if (! preg_match('/^[a-zA-Z0-9-]{0,35}$/D', $language) || ! preg_match('/^(?:[A-Z]{2}|)$/D', $country)) {
            throw new InvalidArgumentException('Invalid metric dimensions.');
        }
        if (! preg_match('/^(?:0|[1-9][0-9]{0,19})(?:\.[0-9]{1,9})?$/D', $value) || ($denominator !== null && $denominator < 0)) {
            throw new InvalidArgumentException('Expected a nonnegative decimal and denominator.');
        }
    }

    public function targetKey(): string
    {
        return $this->targetIdentifier . "\0" . $this->language . "\0" . $this->country;
    }

    public function hasSameTargetAs(self $observation): bool
    {
        return $this->targetKey() === $observation->targetKey() && $this->translationSetIdentifier === $observation->translationSetIdentifier;
    }

    public function validateFor(MetricDefinition $definition, UtcDate $day): void
    {
        if ($definition->metric->grain() !== 'wiki_language_country') {
            throw new InvalidArgumentException('Wiki observations require a Wiki metric.');
        }
        if ($this->targetIdentifier === '' || $this->translationSetIdentifier === '' || $this->language === '') {
            throw new InvalidArgumentException('Wiki observations require target, translation set and language.');
        }
        if ($definition->metric !== Metric::ENGAGEMENT_SECONDS && (str_contains($this->value, '.') || $this->denominator !== null)) {
            throw new InvalidArgumentException('Count metrics require integral values without an average denominator.');
        }
    }
}
