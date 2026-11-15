<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DenyAuthorization;

use Source\Identity\Application\Service\AuthorizationRequestStorageServiceInterface;
use Source\Identity\Application\Service\AuthorizationRequestValidatorInterface;

readonly class DenyAuthorization implements DenyAuthorizationInterface
{
    public function __construct(private AuthorizationRequestStorageServiceInterface $authorizationRequestStorageService, private AuthorizationRequestValidatorInterface $authorizationRequestValidator)
    {
    }

    public function process(DenyAuthorizationInputPort $input, DenyAuthorizationOutputPort $output): void
    {
        $pending = $this->authorizationRequestStorageService->requireValid($input->requestIdentifier(), $input->identityIdentifier());
        $this->authorizationRequestValidator->validate($pending->parameters);
        $redirect = $pending->parameters->redirectUri;
        $output->setRedirectUri($redirect . (str_contains($redirect, '?') ? '&' : '?') . http_build_query(['error' => 'access_denied', 'state' => $pending->parameters->state], '', '&', PHP_QUERY_RFC3986));
        $this->authorizationRequestStorageService->complete($pending->identifier, $input->identityIdentifier());
    }
}
