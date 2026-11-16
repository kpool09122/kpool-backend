<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Application\Models\Identity\AuthorizationConsent as ConsentModel;
use Source\Identity\Domain\Entity\AuthorizationConsent;
use Source\Identity\Domain\Repository\AuthorizationConsentRepositoryInterface;
use Source\Identity\Domain\ValueObject\AccountPrincipalIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationConsentIdentifier;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\OAuthClientIdentifier;
use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationConsentRepository implements AuthorizationConsentRepositoryInterface
{
    public function save(AuthorizationConsent $authorizationConsent): void
    {
        ConsentModel::query()->updateOrCreate(
            ['id' => (string) $authorizationConsent->identifier()],
            [
                'identity_id' => (string) $authorizationConsent->identityIdentifier(),
                'client_id' => (string) $authorizationConsent->clientIdentifier(),
                'scopes' => array_map(static fn (AuthorizationScope $scope): string => $scope->value, $authorizationConsent->scopes()),
                'original_account_id' => (string) $authorizationConsent->originalAccountIdentifier(),
                'original_principal_id' => (string) $authorizationConsent->originalPrincipalIdentifier(),
                'resource' => $authorizationConsent->resource(),
                'consented_at' => $authorizationConsent->consentedAt(),
                'revoked_at' => $authorizationConsent->revokedAt(),
            ],
        );
    }

    public function find(AuthorizationConsentIdentifier $identifier): ?AuthorizationConsent
    {
        $model = ConsentModel::query()->find((string) $identifier);
        if ($model === null) {
            return null;
        }

        return new AuthorizationConsent(
            new AuthorizationConsentIdentifier($model->id),
            new IdentityIdentifier($model->identity_id),
            new OAuthClientIdentifier($model->client_id),
            array_map(AuthorizationScope::from(...), $model->scopes),
            new AccountIdentifier($model->original_account_id),
            new AccountPrincipalIdentifier($model->original_principal_id),
            $model->resource,
            $model->consented_at->toDateTimeImmutable(),
            $model->revoked_at?->toDateTimeImmutable(),
        );
    }
}
