<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Passport;

use Laravel\Passport\Bridge\ScopeRepository as PassportScopeRepository;
use Laravel\Passport\ClientRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

class ScopeRepository extends PassportScopeRepository
{
    public function __construct(ClientRepository $clientRepository, private readonly IssuanceContext $issuanceContext)
    {
        parent::__construct($clientRepository);
    }

    public function finalizeScopes(array $scopes, string $grantType, ClientEntityInterface $clientEntity, ?string $userIdentifier = null, ?string $authCodeId = null): array
    {
        $this->issuanceContext->requireConnection(
            $userIdentifier,
            $clientEntity->getIdentifier(),
            array_map(static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(), $scopes),
        );

        return parent::finalizeScopes($scopes, $grantType, $clientEntity, $userIdentifier, $authCodeId);
    }
}
