<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest;

use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;

interface CreateAuthorizationRequestInterface
{
    /** @throws InvalidAuthorizationRequestException */
    public function process(CreateAuthorizationRequestInputPort $input, CreateAuthorizationRequestOutputPort $output): void;
}
