<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest;

use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class CreateAuthorizationRequestInput implements CreateAuthorizationRequestInputPort
{
    /** @param list<AuthorizationScope> $scopes */
    public function __construct(
        private IdentityIdentifier $identityIdentifier,
        private OAuthClientIdentifier $clientIdentifier,
        private string $redirectUri,
        private array $scopes,
        private string $resource,
        private string $codeChallenge,
        private string $state,
    ) {
    }

    public function identityIdentifier(): IdentityIdentifier
    {
        return $this->identityIdentifier;
    }

    public function clientIdentifier(): OAuthClientIdentifier
    {
        return $this->clientIdentifier;
    }

    public function redirectUri(): string
    {
        return $this->redirectUri;
    }

    /** @return list<AuthorizationScope> */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function resource(): string
    {
        return $this->resource;
    }

    public function codeChallenge(): string
    {
        return $this->codeChallenge;
    }

    public function state(): string
    {
        return $this->state;
    }
}
