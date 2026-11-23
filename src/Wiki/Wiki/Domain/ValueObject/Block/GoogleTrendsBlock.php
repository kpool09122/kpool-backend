<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;

final readonly class GoogleTrendsBlock implements BlockInterface
{
    public const int MAX_SEARCH_TERM_LENGTH = 100;

    public function __construct(
        private int $displayOrder,
        private string $searchTerm,
        private string $region = 'all',
        private string $timeRange = 'today 12-m',
        private ?string $key = null,
    ) {
        if (trim($searchTerm) === '' || mb_strlen($searchTerm) > self::MAX_SEARCH_TERM_LENGTH) {
            throw new InvalidArgumentException('Invalid Google Trends search term.');
        }
        if (! in_array($region, ['all', 'JP', 'KR'], true)) {
            throw new InvalidArgumentException('Invalid Google Trends region.');
        }
        if (! in_array($timeRange, ['today 3-m', 'today 6-m', 'today 12-m', 'today 2-y', 'today 5-y', 'today 10-y'], true)) {
            throw new InvalidArgumentException('Invalid Google Trends time range.');
        }
    }

    public static function defaultSearchTerm(string $name, ?string $groupName = null): string
    {
        return mb_substr(trim($name . ' ' . ($groupName ?? '')), 0, self::MAX_SEARCH_TERM_LENGTH);
    }

    public static function defaultRegion(Language $language): string
    {
        return match ($language) {
            Language::JAPANESE => 'JP',
            Language::KOREAN => 'KR',
            Language::ENGLISH => 'all',
        };
    }

    public function withKey(string $key): self
    {
        return new self($this->displayOrder, $this->searchTerm, $this->region, $this->timeRange, $key);
    }

    public function key(): ?string
    {
        return $this->key;
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function blockType(): BlockType
    {
        return BlockType::GOOGLE_TRENDS;
    }

    public function searchTerm(): string
    {
        return $this->searchTerm;
    }

    public function region(): string
    {
        return $this->region;
    }

    public function timeRange(): string
    {
        return $this->timeRange;
    }
}
