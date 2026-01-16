<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\DeleteImage;

use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;

readonly class DeleteImageInput implements DeleteImageInputPort
{
    public function __construct(
        private ImageIdentifier $imageIdentifier,
        private bool $isDraft,
    ) {
    }

    public function imageIdentifier(): ImageIdentifier
    {
        return $this->imageIdentifier;
    }

    public function isDraft(): bool
    {
        return $this->isDraft;
    }
}
