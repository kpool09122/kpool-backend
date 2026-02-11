<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\RejectWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface RejectWikiInputPort
{
    public function wikiIdentifier(): DraftWikiIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;

    public function resourceType(): ResourceType;

    public function agencyIdentifier(): ?WikiIdentifier;

    /** @return WikiIdentifier[] */
    public function groupIdentifiers(): array;

    /** @return WikiIdentifier[] */
    public function talentIdentifiers(): array;
}
