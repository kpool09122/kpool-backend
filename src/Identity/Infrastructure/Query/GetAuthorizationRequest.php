<?php

declare(strict_types=1);

namespace Source\Identity\Infrastructure\Query;

use Source\Identity\Application\Service\AuthorizationAccountServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\AuthorizationRequestReadModel;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInputPort;
use Source\Identity\Application\UseCase\Query\GetAuthorizationRequest\GetAuthorizationRequestInterface;

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

        return new AuthorizationRequestReadModel($pending, $clientName);
    }
}
