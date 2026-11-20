<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

final readonly class HistoryBlock implements BlockInterface
{
    /** @param list<HistoryEntry> $entries */
    public function __construct(
        private int $displayOrder,
        private array $entries,
        private ?string $key = null,
    ) {
    }

    public function withKey(string $key): self
    {
        return new self(
            displayOrder: $this->displayOrder,
            entries: $this->entries,
            key: $key,
        );
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
        return BlockType::HISTORY;
    }

    /** @return list<HistoryEntry> */
    public function entries(): array
    {
        return $this->entries;
    }
}
