<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthorizationRequest;

use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface GetAuthorizationRequestInputPort
{
    public function requestIdentifier(): string;

    public function identityIdentifier(): IdentityIdentifier;
}
