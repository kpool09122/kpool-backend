<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\RejectImage;

use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;

interface RejectImageInputPort
{
    public function imageIdentifier(): ImageIdentifier;
}
