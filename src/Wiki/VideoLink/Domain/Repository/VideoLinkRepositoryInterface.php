<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Repository;

use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;

interface VideoLinkRepositoryInterface
{
    public function findById(VideoLinkIdentifier $identifier): ?VideoLink;

    /**
     * @return VideoLink[]
     */
    public function findByResource(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier): array;

    public function save(VideoLink $videoLink): void;

    public function delete(VideoLinkIdentifier $identifier): void;

    public function deleteByResource(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier): void;

    public function deleteAutoCollectedByResource(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier): void;

    public function findByResourceWithMaxDisplayOrder(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier): ?VideoLink;

    /**
     * 指定したリソースに紐づく動画リンクのうち、指定したURLに該当するものを返却する.
     *
     * @param string[] $urls
     *
     * @return VideoLink[]
     */
    public function findByResourceAndUrls(ResourceType $resourceType, ResourceIdentifier $resourceIdentifier, array $urls): array;
}
