<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup;

use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface AutomaticCreateDraftGroupInputPort
{
    public function payload(): AutomaticDraftGroupCreationPayload;

    public function principalIdentifier(): PrincipalIdentifier;
}
