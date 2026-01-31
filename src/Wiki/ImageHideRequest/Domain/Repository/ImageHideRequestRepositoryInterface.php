<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Domain\Repository;

use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;

interface ImageHideRequestRepositoryInterface
{
    public function save(ImageHideRequest $entity): void;

    public function findById(ImageHideRequestIdentifier $id): ?ImageHideRequest;

    public function existsPendingByImageId(ImageIdentifier $imageIdentifier): bool;
}
