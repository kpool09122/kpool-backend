<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthorizationRequest;

use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;
use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;

interface GetAuthorizationRequestInterface
{
    /** @throws AuthorizationAccountUnavailableException|AuthorizationRequestUnavailableException|InvalidAuthorizationRequestException */
    public function process(GetAuthorizationRequestInputPort $input): AuthorizationRequestReadModel;
}
