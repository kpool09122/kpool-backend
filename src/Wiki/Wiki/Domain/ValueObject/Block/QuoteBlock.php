<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

final readonly class QuoteBlock implements BlockInterface
{
    public function __construct(
        private int $displayOrder,
        private string $content,
        private ?string $source = null,
        private ?string $key = null,
    ) {
    }

    public function withKey(string $key): self
    {
        return new self(
            displayOrder: $this->displayOrder,
            content: $this->content,
            source: $this->source,
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
