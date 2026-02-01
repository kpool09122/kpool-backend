<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\UnhideImage;

use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface UnhideImageInputPort
{
    public function imageIdentifier(): ImageIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;
}
