<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Image\Domain\Entity\DraftImage;
use Source\Wiki\Shared\Domain\ValueObject\ImageIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

interface DraftImageRepositoryInterface
{
    public function findById(ImageIdentifier $identifier): ?DraftImage;

    /**
     * @return DraftImage[]
     */
    public function findByDraftResource(ResourceType $resourceType, TranslationSetIdentifier $translationSetIdentifier): array;

    public function save(DraftImage $draftImage): void;

    public function delete(ImageIdentifier $identifier): void;

    /**
     * @param ResourceType $resourceType
     * @param TranslationSetIdentifier $translationSetIdentifier
     */
    public function deleteByDraftResource(ResourceType $resourceType, TranslationSetIdentifier $translationSetIdentifier): void;
}
