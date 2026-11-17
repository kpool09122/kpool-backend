<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Repository;

use Application\Models\Identity\AuthorizationScopeSettings as SettingsModel;
use Source\Identity\Domain\Entity\AuthorizationScopeSettings;
use Source\Identity\Domain\Repository\AuthorizationScopeSettingsRepositoryInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\AuthorizationScopeSettingsIdentifier;
use Source\Shared\Domain\ValueObject\IdentityIdentifier;

readonly class AuthorizationScopeSettingsRepository implements AuthorizationScopeSettingsRepositoryInterface
{
    public function find(IdentityIdentifier $identityIdentifier): ?AuthorizationScopeSettings
    {
        $model = SettingsModel::query()->where('identity_id', (string) $identityIdentifier)->first();

        return $model === null ? null : new AuthorizationScopeSettings(new AuthorizationScopeSettingsIdentifier($model->id), $identityIdentifier, array_map(AuthorizationScope::from(...), $model->scopes));
    }

    public function save(AuthorizationScopeSettings $authorizationScopeSettings): void
    {
        SettingsModel::query()->updateOrCreate(
            ['id' => (string) $authorizationScopeSettings->identifier()],
            ['identity_id' => (string) $authorizationScopeSettings->identityIdentifier(), 'scopes' => array_map(static fn (AuthorizationScope $scope): string => $scope->value, $authorizationScopeSettings->scopes())],
        );
    }
}
