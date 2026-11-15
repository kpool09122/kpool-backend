<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest;

use Source\Identity\Application\Service\AuthorizationCodeParameters;
use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;

readonly class CreateAuthorizationRequest implements CreateAuthorizationRequestInterface
{
    public function __construct(private AuthorizationRequestValidatorInterface $authorizationRequestValidator, private AuthorizationRequestStorageServiceInterface $authorizationRequestStorageService)
    {
    }

    public function process(CreateAuthorizationRequestInputPort $input, CreateAuthorizationRequestOutputPort $output): void
    {
        $parameters = new AuthorizationCodeParameters(
            $input->clientIdentifier(),
            $input->redirectUri(),
            $input->scopes(),
            $input->resource(),
            $input->codeChallenge(),
            $input->state(),
        );
        $this->authorizationRequestValidator->validate($parameters);
        $output->setPending($this->authorizationRequestStorageService->store($parameters, $input->identityIdentifier()));
    }
}
