<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

readonly class RollbackWikiInput implements RollbackWikiInputPort
{
    /**
     * @param PrincipalIdentifier $principalIdentifier
     * @param WikiIdentifier $wikiIdentifier
     * @param Version $targetVersion
     * @param ResourceType $resourceType
     * @param WikiIdentifier|null $agencyIdentifier
     * @param WikiIdentifier[] $groupIdentifiers
     * @param WikiIdentifier[] $talentIdentifiers
     */
    public function __construct(
        private PrincipalIdentifier $principalIdentifier,
        private WikiIdentifier      $wikiIdentifier,
        private Version             $targetVersion,
        private ResourceType        $resourceType,
        private ?WikiIdentifier     $agencyIdentifier = null,
        private array               $groupIdentifiers = [],
        private array               $talentIdentifiers = [],
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function wikiIdentifier(): WikiIdentifier
    {
        return $this->wikiIdentifier;
    }

    public function targetVersion(): Version
    {
        return $this->targetVersion;
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
