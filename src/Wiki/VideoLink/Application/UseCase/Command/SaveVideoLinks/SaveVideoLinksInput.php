<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class SaveVideoLinksInput implements SaveVideoLinksInputPort
{
    /**
     * @param PrincipalIdentifier $principalIdentifier
     * @param ResourceType $resourceType
     * @param ResourceIdentifier $resourceIdentifier
     * @param VideoLinkData[] $videoLinks
     */
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ResourceType $resourceType,
        private ResourceIdentifier $resourceIdentifier,
        private array $videoLinks,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function resourceIdentifier(): ResourceIdentifier
    {
        return $this->resourceIdentifier;
    }

    /**
     * @return VideoLinkData[]
     */
    public function videoLinks(): array
    {
        return $this->videoLinks;
    }
}
