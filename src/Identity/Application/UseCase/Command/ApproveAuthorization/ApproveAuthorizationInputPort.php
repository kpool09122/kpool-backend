<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\ApproveAuthorization;

use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationTarget;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface ApproveAuthorizationInputPort
{
    public function requestIdentifier(): string;

    public function identityIdentifier(): IdentityIdentifier;

    /** @return list<AuthorizationScope> */
    public function scopes(): array;

    /** @return list<AuthorizationTarget> */
    public function targets(): array;

    public function initialAccountIdentifier(): AccountIdentifier;
}
