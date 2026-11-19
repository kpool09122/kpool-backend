<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Models\Identity\AuthorizationScopeSettings;
use Source\Identity\Application\Service\StepUpAuthenticationStorageServiceInterface;
use Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings\AuthorizationScopeSettingsReadModel;
use Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings\GetAuthorizationScopeSettingsInputPort;
use Source\Identity\Application\UseCase\Query\GetAuthorizationScopeSettings\GetAuthorizationScopeSettingsInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;
use Source\Identity\Domain\ValueObject\StepUpAuthenticationScope;

readonly class GetAuthorizationScopeSettings implements GetAuthorizationScopeSettingsInterface
{
    public function __construct(
        private StepUpAuthenticationStorageServiceInterface $stepUpAuthenticationStorageService,
    ) {
    }

    /** @phpstan-impure */
    public function process(GetAuthorizationScopeSettingsInputPort $input): AuthorizationScopeSettingsReadModel
    {
        $this->stepUpAuthenticationStorageService->requireValid($input->identityIdentifier(), StepUpAuthenticationScope::RECENT_AUTHENTICATION);

        $model = AuthorizationScopeSettings::query()->where('identity_id', (string) $input->identityIdentifier())->first();
        $scopes = $model === null ? [] : array_map(AuthorizationScope::from(...), $model->scopes);

        return new AuthorizationScopeSettingsReadModel($scopes);
    }
}
