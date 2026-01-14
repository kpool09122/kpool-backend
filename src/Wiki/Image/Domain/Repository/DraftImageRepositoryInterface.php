<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Repository;

use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Image\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface DraftImageRepositoryInterface
{
    public function findById(ImageIdentifier $identifier): ?DraftImage;

    /**
     * @return DraftImage[]
     */
    public function findByDraftResource(ResourceType $resourceType, ResourceIdentifier $draftResourceIdentifier): array;

    public function save(DraftImage $draftImage): void;

    public function delete(ImageIdentifier $identifier): void;

    /**
     * @param ResourceType $resourceType
     * @param ResourceIdentifier $draftResourceIdentifier
     */
    public function deleteByDraftResource(ResourceType $resourceType, ResourceIdentifier $draftResourceIdentifier): void;
}
