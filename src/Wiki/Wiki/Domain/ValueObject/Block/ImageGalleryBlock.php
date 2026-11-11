<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;

final readonly class ImageGalleryBlock implements BlockInterface
{
    /**
     * @param array<ImageIdentifier> $imageIdentifiers
     */
    public function __construct(
        private int $displayOrder,
        private array $imageIdentifiers,
        private ?string $caption = null,
        private ?int $imageWidth = null,
    ) {
        if ($imageWidth !== null && $imageWidth < 1) {
            throw new InvalidArgumentException('Image width must be a positive pixel value.');
        }
    }

    public function imageWidth(): ?int
    {
        return $this->imageWidth;
    }

    public function displayOrder(): int
    {
        return $this->displayOrder;
    }

    public function blockType(): BlockType
    {
        return BlockType::IMAGE_GALLERY;
    }

    /**
     * @return array<ImageIdentifier>
     */
    public function imageIdentifiers(): array
    {
        return $this->imageIdentifiers;
    }

    public function caption(): ?string
    {
        return $this->caption;
    }
}
