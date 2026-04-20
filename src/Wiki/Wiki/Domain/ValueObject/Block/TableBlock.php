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
