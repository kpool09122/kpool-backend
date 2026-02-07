<?php

declare(strict_types=1);

namespace Source\Wiki\Wiki\Application\UseCase\Command\AutoCreateWiki;

use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Wiki\Domain\ValueObject\AutoWikiCreationPayload;

readonly class AutoCreateWikiInput implements AutoCreateWikiInputPort
{
    public function __construct(
        private AutoWikiCreationPayload $payload,
        private PrincipalIdentifier     $principalIdentifier,
    ) {
    }

    public function payload(): AutoWikiCreationPayload
    {
        return $this->payload;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
