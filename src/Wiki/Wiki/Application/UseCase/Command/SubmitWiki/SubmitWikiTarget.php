<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use Source\Shared\Domain\ValueObject\WikiIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;

readonly class SubmitWikiTarget
{
    /**
     * @param DraftWikiIdentifier $wikiIdentifier
     * @param ResourceType $resourceType
     * @param WikiIdentifier|null $agencyIdentifier
     * @param WikiIdentifier[] $groupIdentifiers
     * @param WikiIdentifier[] $talentIdentifiers
     */
    public function __construct(
        private DraftWikiIdentifier $wikiIdentifier,
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
