<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Domain\Repository;

use Source\Wiki\ImageHideRequest\Domain\Entity\ImageHideRequest;
use Source\Wiki\ImageHideRequest\Domain\ValueObject\ImageHideRequestIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;

interface ImageHideRequestRepositoryInterface
{
    public function save(ImageHideRequest $entity): void;

    public function findById(ImageHideRequestIdentifier $id): ?ImageHideRequest;

    public function existsPendingByImageId(ImageIdentifier $imageIdentifier): bool;
}
