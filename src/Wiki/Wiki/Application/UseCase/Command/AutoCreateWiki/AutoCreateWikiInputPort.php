<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;

interface AutoCreateWikiInputPort
{
    public function payload(): AutoWikiCreationPayload;

    public function principalIdentifier(): PrincipalIdentifier;
}
