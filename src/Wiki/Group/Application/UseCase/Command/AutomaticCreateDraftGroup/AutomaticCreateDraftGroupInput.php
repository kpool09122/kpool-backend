<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup;

use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class AutomaticCreateDraftGroupInput implements AutomaticCreateDraftGroupInputPort
{
    public function __construct(
        private AutomaticDraftGroupCreationPayload $payload,
        private PrincipalIdentifier                $principalIdentifier,
    ) {
    }

    public function payload(): AutomaticDraftGroupCreationPayload
    {
        return $this->payload;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
