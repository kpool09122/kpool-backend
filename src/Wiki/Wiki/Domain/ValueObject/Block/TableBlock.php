<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

final readonly class TableBlock implements BlockInterface
{
    /**
     * @param array<array<TableCell>> $rowCells
     * @param array<TableCell>|null $headerCells
     */
    public function __construct(
        private int $displayOrder,
        private array $rowCells,
        private ?array $headerCells = null,
        private ?string $tableWidth = null,
        private ?string $key = null,
    ) {
    }

    public function withKey(string $key): self
    {
        return new self(
            displayOrder: $this->displayOrder,
            rowCells: $this->rowCells,
            headerCells: $this->headerCells,
            tableWidth: $this->tableWidth,
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
        return BlockType::TABLE;
    }

    /**
     * @return array<array<TableCell>>
     */
    public function rowCells(): array
    {
        return $this->rowCells;
    }

    /**
     * @return array<TableCell>|null
     */
    public function headerCells(): ?array
    {
        return $this->headerCells;
    }

    public function tableWidth(): ?string
    {
        return $this->tableWidth;
    }
}
