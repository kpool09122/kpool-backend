<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

final readonly class QuoteBlock implements BlockInterface
{
    public function __construct(
        private int $displayOrder,
        private string $content,
        private ?string $source = null,
    ) {
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function blockType(): BlockType
    {
        return BlockType::QUOTE;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function source(): ?string
    {
        return $this->source;
    }
}
