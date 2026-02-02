<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

final readonly class ProfileCardListBlock implements BlockInterface
{
    /**
     * @param array<WikiIdentifier> $wikiIdentifiers
     */
    public function __construct(
        private int $displayOrder,
        private array $wikiIdentifiers,
        private ?string $title = null,
    ) {
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

    public function title(): ?string
    {
        return $this->title;
    }
}
