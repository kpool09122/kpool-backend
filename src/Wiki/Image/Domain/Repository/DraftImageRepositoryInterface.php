<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Repository;

use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface DraftImageRepositoryInterface
{
    public function findById(ImageIdentifier $identifier): ?DraftImage;

    /**
     * @return DraftImage[]
     */
    public function findByDraftResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): array;

    public function save(DraftImage $draftImage): void;

    public function delete(ImageIdentifier $identifier): void;

    /**
     * @param ResourceType $resourceType
     * @param WikiIdentifier $wikiIdentifier
     */
    public function deleteByDraftResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): void;
}
