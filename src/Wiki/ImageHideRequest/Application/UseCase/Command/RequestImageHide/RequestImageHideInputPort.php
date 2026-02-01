<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Application\UseCase\Command\RequestImageHide;

use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;

interface RequestImageHideInputPort
{
    public function imageIdentifier(): ImageIdentifier;

    public function requesterName(): string;

    public function requesterEmail(): string;

    public function reason(): string;
}
