<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

final readonly class TextBlock implements BlockInterface
{
    public function __construct(
        private int $displayOrder,
        private string $content,
        private ?string $key = null,
    ) {
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
        return BlockType::TEXT;
    }

    public function content(): string
    {
        return $this->content;
    }
}
