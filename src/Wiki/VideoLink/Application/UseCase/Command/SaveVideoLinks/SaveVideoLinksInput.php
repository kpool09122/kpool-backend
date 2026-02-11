<?php

declare(strict_types=1);

namespace Source\Wiki\VideoLink\Application\UseCase\Command\SaveVideoLinks;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class SaveVideoLinksInput implements SaveVideoLinksInputPort
{
    /**
     * @param PrincipalIdentifier $principalIdentifier
     * @param ResourceType $resourceType
     * @param WikiIdentifier $wikiIdentifier
     * @param VideoLinkData[] $videoLinks
     */
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private ResourceType        $resourceType,
        private WikiIdentifier      $wikiIdentifier,
        private array               $videoLinks,
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

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    /**
     * @return VideoLinkData[]
     */
    public function videoLinks(): array
    {
        return $this->videoLinks;
    }
}
