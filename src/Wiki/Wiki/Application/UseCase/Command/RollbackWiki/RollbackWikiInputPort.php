<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\RollbackWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface RollbackWikiInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function wikiIdentifier(): WikiIdentifier;

    public function targetVersion(): Version;

    public function resourceType(): ResourceType;

    public function agencyIdentifier(): ?WikiIdentifier;

    /** @return WikiIdentifier[] */
    public function groupIdentifiers(): array;

    /** @return WikiIdentifier[] */
    public function talentIdentifiers(): array;
}
