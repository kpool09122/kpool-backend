<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

final readonly class TextBlock implements BlockInterface
{
    public function __construct(
        private int $displayOrder,
        private string $content,
    ) {
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function blockType(): BlockType
    {
        return BlockType::TEXT;
    }

    public function content(): string
    {
        return $this->content;
    }
}
