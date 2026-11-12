<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

use InvalidArgumentException;

final readonly class HistoryEntry
{
    public function __construct(
        public int $year,
        public string $title,
        public ?string $detail = null,
    ) {
        if ($year < 1000 || $year > 9999 || trim($title) === '') {
            throw new InvalidArgumentException('History entries require a four-digit year and a title.');
        }
    }

    /** @return array{year: int, title: string, detail: ?string} */
    public function toArray(): array
    {
        return ['year' => $this->year, 'title' => $this->title, 'detail' => $this->detail];
    }
}
