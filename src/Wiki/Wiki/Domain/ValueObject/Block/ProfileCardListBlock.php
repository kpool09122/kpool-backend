<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

use Source\Shared\Domain\ValueObject\WikiIdentifier;

final readonly class ProfileCardListBlock implements BlockInterface
{
    /**
     * @param array<WikiIdentifier> $wikiIdentifiers
     */
    public function __construct(
        private int $displayOrder,
        private array $wikiIdentifiers,
        private ProfileCardShape $cardShape,
        private ?string $title = null,
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
        return BlockType::PROFILE_CARD_LIST;
    }

    /**
     * @return array<WikiIdentifier>
     */
    public function wikiIdentifiers(): array
    {
        return $this->wikiIdentifiers;
    }

    public function cardShape(): ProfileCardShape
    {
        return $this->cardShape;
    }

    public function title(): ?string
    {
        return $this->title;
    }
}
