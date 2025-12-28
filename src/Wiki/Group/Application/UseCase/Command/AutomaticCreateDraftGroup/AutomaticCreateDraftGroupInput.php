<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutomaticCreateDraftGroup;

use Source\Wiki\Group\Domain\ValueObject\AutomaticDraftGroupCreationPayload;
use Source\Wiki\Principal\Domain\Entity\Principal;

readonly class AutomaticCreateDraftGroupInput implements AutomaticCreateDraftGroupInputPort
{
    public function __construct(
        private AutomaticDraftGroupCreationPayload $payload,
        private Principal $principal,
    ) {
    }

    public function payload(): AutomaticDraftGroupCreationPayload
    {
        return $this->payload;
    }

    public function principal(): Principal
    {
        return $this->principal;
    }
}
