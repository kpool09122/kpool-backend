<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\UseCase\Command\AutoCreateGroup;

use Source\Wiki\Group\Domain\ValueObject\AutoGroupCreationPayload;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

readonly class AutoCreateGroupInput implements AutoCreateGroupInputPort
{
    public function __construct(
        private AutoGroupCreationPayload $payload,
        private PrincipalIdentifier      $principalIdentifier,
    ) {
    }

    public function payload(): AutoGroupCreationPayload
    {
        return $this->payload;
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }
}
