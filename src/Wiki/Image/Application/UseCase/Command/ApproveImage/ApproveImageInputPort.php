<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\ApproveImage;

use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;

interface ApproveImageInputPort
{
    public function imageIdentifier(): ImageIdentifier;
}
