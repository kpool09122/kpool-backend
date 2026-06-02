<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\DeleteWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\DraftWikiIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\WikiIdentifier;

interface DeleteWikiInputPort
{
    public function wikiIdentifier(): DraftWikiIdentifier;

    public function principalIdentifier(): PrincipalIdentifier;

    public function agencyIdentifier(): ?WikiIdentifier;

    /** @return WikiIdentifier[] */
    public function groupIdentifiers(): array;

    /** @return WikiIdentifier[] */
    public function talentIdentifiers(): array;
}
