<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\SubmitWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\VisitorLocation;

interface SubmitWikiInputPort
{
    /** @return list<SubmitWikiTarget> */
    public function targets(): array;

    public function principalIdentifier(): PrincipalIdentifier;

    public function visitorLocation(): VisitorLocation;
}
