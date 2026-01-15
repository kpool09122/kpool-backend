<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\DeleteImage;

use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;

interface DeleteImageInputPort
{
    public function imageIdentifier(): ImageIdentifier;

    public function isDraft(): bool;
}
