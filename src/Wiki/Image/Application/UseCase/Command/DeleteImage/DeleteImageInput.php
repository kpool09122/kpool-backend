<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\UseCase\Command\DeleteImage;

use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class DeleteImageInput implements DeleteImageInputPort
{
    public function __construct(
        private ImageIdentifier $imageIdentifier,
        private PrincipalIdentifier $principalIdentifier,
    ) {
    }

    public function imageIdentifier(): ImageIdentifier
    {
        return $this->imageIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
