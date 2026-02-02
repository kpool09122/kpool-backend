<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

final readonly class EmbedBlock implements BlockInterface
{
    public function __construct(
        private int $displayOrder,
        private EmbedProvider $provider,
        private string $embedId,
        private ?string $caption = null,
    ) {
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function blockType(): BlockType
    {
        return BlockType::EMBED;
    }

    public function provider(): EmbedProvider
    {
        return $this->provider;
    }

    public function embedId(): string
    {
        return $this->embedId;
    }

    public function caption(): ?string
    {
        return $this->caption;
    }
}
