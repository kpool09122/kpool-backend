<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Domain\Repository;

use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\VideoLink\Domain\Entity\VideoLink;
use Source\Wiki\VideoLink\Domain\ValueObject\VideoLinkIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface VideoLinkRepositoryInterface
{
    public function findById(VideoLinkIdentifier $identifier): ?VideoLink;

    /**
     * @return VideoLink[]
     */
    public function findByResource(ResourceType $resourceType, WikiIdentifier $wikiIdentifier): array;

    public function save(VideoLink $videoLink): void;

    public function delete(VideoLinkIdentifier $identifier): void;

    public function deleteByResource(ResourceType $resourceType, WikiIdentifier $resourceIdentifier): void;

    public function deleteAutoCollectedByResource(ResourceType $resourceType, WikiIdentifier $resourceIdentifier): void;

    public function findByResourceWithMaxDisplayOrder(ResourceType $resourceType, WikiIdentifier $resourceIdentifier): ?VideoLink;

    /**
     * 指定したリソースに紐づく動画リンクのうち、指定したURLに該当するものを返却する.
     *
     * @param string[] $urls
     *
     * @return VideoLink[]
     */
    public function findByResourceAndUrls(ResourceType $resourceType, WikiIdentifier $resourceIdentifier, array $urls): array;
}
