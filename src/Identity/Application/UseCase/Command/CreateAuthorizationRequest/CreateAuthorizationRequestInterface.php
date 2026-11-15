<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest;

use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;

interface CreateAuthorizationRequestInterface
{
    /** @throws InvalidAuthorizationConsentException */
    public function process(CreateAuthorizationRequestInputPort $input, CreateAuthorizationRequestOutputPort $output): void;
}
