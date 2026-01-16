<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImage;

use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;

readonly class RejectImageInput implements RejectImageInputPort
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
