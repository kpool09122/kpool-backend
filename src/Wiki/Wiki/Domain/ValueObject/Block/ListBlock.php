<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

final readonly class ListBlock implements BlockInterface
{
    /**
     * @param array<string> $items
     */
    public function __construct(
        private int $displayOrder,
        private ListType $listType,
        private array $items,
    ) {
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function blockType(): BlockType
    {
        return BlockType::LIST;
    }

    public function listType(): ListType
    {
        return $this->listType;
    }

    /**
     * @return array<string>
     */
    public function items(): array
    {
        return $this->items;
    }
}
