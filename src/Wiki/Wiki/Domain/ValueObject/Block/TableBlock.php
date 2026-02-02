<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

final readonly class TableBlock implements BlockInterface
{
    /**
     * @param array<array<string>> $rows
     * @param array<string>|null $headers
     */
    public function __construct(
        private int $displayOrder,
        private array $rows,
        private ?array $headers = null,
    ) {
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function blockType(): BlockType
    {
        return BlockType::TABLE;
    }

    /**
     * @return array<array<string>>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * @return array<string>|null
     */
    public function headers(): ?array
    {
        return $this->headers;
    }
}
