<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Application\Models\Identity\AuthorizationScopeSettings;
use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\AuthorizationRequestReadModel;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInputPort;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInterface;
use Source\Identity\Domain\ValueObject\AuthorizationScope;

readonly class GetAuthorizationRequest implements GetAuthorizationRequestInterface
{
    public function __construct(
        private AuthorizationRequestStorageServiceInterface $authorizationRequestStorageService,
        private AuthorizationRequestValidatorInterface $authorizationRequestValidator,
        private AuthorizationAccountServiceInterface $authorizationAccountService,
    ) {
    }

    public function process(GetAuthorizationRequestInputPort $input): AuthorizationRequestReadModel
    {
        $identityIdentifier = $input->identityIdentifier();
        $pending = $this->authorizationRequestStorageService->requireValid($input->requestIdentifier(), $identityIdentifier);
        $clientName = $this->authorizationRequestValidator->validate($pending->parameters);
        $this->authorizationAccountService->resolveOriginalAccount($identityIdentifier);

        $settings = AuthorizationScopeSettings::query()->where('identity_id', (string) $identityIdentifier)->first();
        $allowedScopes = $settings === null ? [] : array_map(AuthorizationScope::from(...), $settings->scopes);
        $scopes = array_values(array_filter($pending->parameters->requestedScopes, static fn (AuthorizationScope $scope): bool => in_array($scope, $allowedScopes, true)));

        return new AuthorizationRequestReadModel($pending, $clientName, $scopes);
    }
}
