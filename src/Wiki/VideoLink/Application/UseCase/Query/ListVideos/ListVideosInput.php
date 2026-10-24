<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Query\ListVideos;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

readonly class ListVideosInput implements ListVideosInputPort
{
    public function __construct(
        private Slug $slug,
        private Language $language,
        private ResourceType $resourceType,
        private ?int $minDurationSeconds = null,
        private ?int $maxDurationSeconds = null,
    ) {
        foreach ([$minDurationSeconds, $maxDurationSeconds] as $seconds) {
            if ($seconds !== null && ($seconds < 0 || $seconds > 2147483647)) {
                throw new InvalidArgumentException('Invalid video duration bound.');
            }
        }
        if ($minDurationSeconds !== null && $maxDurationSeconds !== null && $minDurationSeconds > $maxDurationSeconds) {
            throw new InvalidArgumentException('Minimum duration exceeds maximum duration.');
        }
    }

    public function minDurationSeconds(): ?int
    {
        return $this->minDurationSeconds;
    }

    public function maxDurationSeconds(): ?int
    {
        return $this->maxDurationSeconds;
    }

    public function slug(): Slug
    {
        return $this->slug;
    }

    public function language(): Language
    {
        return $this->language;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }
}
