<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\ApproveAuthorization;

use Source\Identity\Application\Exception\AuthorizationAccountUnavailableException;
use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Source\Identity\Application\Exception\InvalidAuthorizationRequestException;

interface ApproveAuthorizationInterface
{
    /** @throws AuthorizationAccountUnavailableException|AuthorizationRequestUnavailableException|InvalidAuthorizationRequestException */
    public function process(ApproveAuthorizationInputPort $input, ApproveAuthorizationOutputPort $output): void;
}
