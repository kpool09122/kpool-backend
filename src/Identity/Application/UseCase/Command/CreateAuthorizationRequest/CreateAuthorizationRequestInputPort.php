<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest;

use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

interface CreateAuthorizationRequestInputPort
{
    public function identityIdentifier(): IdentityIdentifier;

    public function clientIdentifier(): OAuthClientIdentifier;

    public function redirectUri(): string;

    /** @return list<AuthorizationScope> */
    public function scopes(): array;

    public function resource(): string;

    public function codeChallenge(): string;

    public function state(): string;
}
