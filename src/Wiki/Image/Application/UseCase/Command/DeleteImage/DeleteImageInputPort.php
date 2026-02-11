<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\DeleteImage;

use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface DeleteImageInputPort
{
    public function imageIdentifier(): ImageIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
