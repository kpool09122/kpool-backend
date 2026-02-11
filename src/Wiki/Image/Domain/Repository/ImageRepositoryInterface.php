<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Repository;

use Source\Wiki\Image\Domain\Entity\Image;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface ImageRepositoryInterface
{
    public function findById(ImageIdentifier $identifier): ?Image;

    /**
     * @return Image[]
     */
    public function findByResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): array;

    public function save(Image $image): void;

    public function delete(ImageIdentifier $identifier): void;
}
