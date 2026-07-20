<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\DeletePrincipalGroup;

use Source\Account\Shared\Domain\ValueObject\PrincipalGroupIdentifier;

readonly class DeletePrincipalGroupInput implements DeletePrincipalGroupInputPort
{
    public function __construct(
        private PrincipalGroupIdentifier $principalGroupIdentifier,
    ) {
    }

    public function principalGroupIdentifier(): PrincipalGroupIdentifier
    {
        return $this->principalGroupIdentifier;
    }
}
