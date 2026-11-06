<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Domain\ValueObject;

use InvalidArgumentException;
use Source\Analyze\Shared\Domain\Exception\MetricDefinitionMismatchException;

readonly class MetricDefinition
{
    public function __construct(
        public string $source,
        public Metric $metric,
        public string $grain,
        public string $version,
    ) {
        foreach ([$source, $grain, $version] as $key) {
            if (! preg_match('/^[a-zA-Z0-9_.:-]{1,80}$/D', $key)) {
                throw new InvalidArgumentException('Invalid metric definition key.');
            }
        }
        if ($grain !== $metric->grain()) {
            throw new InvalidArgumentException('Invalid metric grain.');
        }
    }

    /**
     * @throws MetricDefinitionMismatchException
     */
    public function assertMatches(self $definition): void
    {
        if ($this != $definition) {
            throw new MetricDefinitionMismatchException();
        }
    }
}
