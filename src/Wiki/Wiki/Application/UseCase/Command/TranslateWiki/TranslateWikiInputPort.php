<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\TranslateWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface TranslateWikiInputPort
{
    public function wikiIdentifier(): WikiIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;

    public function resourceType(): ResourceType;

    public function agencyIdentifier(): ?WikiIdentifier;

    /** @return WikiIdentifier[] */
    public function groupIdentifiers(): array;

    /** @return WikiIdentifier[] */
    public function talentIdentifiers(): array;
}
