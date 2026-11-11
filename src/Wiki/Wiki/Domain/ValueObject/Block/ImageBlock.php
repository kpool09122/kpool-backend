<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Domain\ValueObject\Block;

use InvalidArgumentException;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;

final readonly class ImageBlock implements BlockInterface
{
    public function __construct(
        private int $displayOrder,
        private ImageIdentifier $imageIdentifier,
        private ?string $caption = null,
        private ?string $alt = null,
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
        return BlockType::IMAGE;
    }

    public function imageIdentifier(): ImageIdentifier
    {
        return $this->imageIdentifier;
    }

    public function caption(): ?string
    {
        return $this->caption;
    }

    public function alt(): ?string
    {
        return $this->alt;
    }
}
