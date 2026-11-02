<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Domain\ValueObject;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * 計測の対象日、取得元・定義、更新日時と品質情報。分析値そのものは持たない。
 */
readonly class MetricProvenance
{
    /**
     * @param list<string> $quality
     */
    public function __construct(
        private MetricDefinition $definition,
        private UtcDate $day,
        private DateTimeImmutable $updatedAt,
        private array $quality = [],
    ) {
        foreach ($quality as $code) {
            if (! preg_match('/^[a-z0-9_.:-]{1,80}$/D', $code)) {
                throw new InvalidArgumentException('Quality information must contain codes.');
            }
        }
    }

    public function definition(): MetricDefinition
    {
        return $this->definition;
    }

    public function day(): UtcDate
    {
        return $this->day;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return list<string> */
    public function quality(): array
    {
        return $this->quality;
    }
}
