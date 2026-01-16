<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImage;

use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;

readonly class ApproveImageInput implements ApproveImageInputPort
{
    public function __construct(
        private ImageIdentifier $imageIdentifier,
    ) {
    }

    public function imageIdentifier(): ImageIdentifier
    {
        return $this->imageIdentifier;
    }
}
