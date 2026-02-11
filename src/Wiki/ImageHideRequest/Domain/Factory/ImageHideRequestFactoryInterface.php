<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Domain\Factory;

use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;

interface ImageHideRequestFactoryInterface
{
    public function create(
        ImageIdentifier $imageIdentifier,
        string $requesterName,
        string $requesterEmail,
        string $reason,
    ): ImageHideRequest;
}
