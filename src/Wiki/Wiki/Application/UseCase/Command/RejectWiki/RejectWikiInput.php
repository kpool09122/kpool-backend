<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class RejectWikiInput implements RejectWikiInputPort
{
    /**
     * @param DraftWikiIdentifier $wikiIdentifier
     * @param PrincipalIdentifier $principalIdentifier
     * @param ResourceType $resourceType
     * @param WikiIdentifier|null $agencyIdentifier
     * @param WikiIdentifier[] $groupIdentifiers
     * @param WikiIdentifier[] $talentIdentifiers
     */
    public function __construct(
        private DraftWikiIdentifier $wikiIdentifier,
        private PrincipalIdentifier $principalIdentifier,
        private ResourceType        $resourceType,
        private ?WikiIdentifier     $agencyIdentifier = null,
        private array               $groupIdentifiers = [],
        private array               $talentIdentifiers = [],
    ) {
    }

    public function wikiIdentifier(): DraftWikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function resourceType(): ResourceType
    {
        return $this->resourceType;
    }

    public function agencyIdentifier(): ?WikiIdentifier
    {
        return $this->agencyIdentifier;
    }

    /** @return WikiIdentifier[] */
    public function groupIdentifiers(): array
    {
        return $this->groupIdentifiers;
    }

    /** @return WikiIdentifier[] */
    public function talentIdentifiers(): array
    {
        return $this->talentIdentifiers;
    }
}
