<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\DenyAuthorization;

use Source\Identity\Application\Exception\AuthorizationRequestUnavailableException;
use Source\Identity\Application\Exception\InvalidAuthorizationConsentException;

interface DenyAuthorizationInterface
{
    /** @throws AuthorizationRequestUnavailableException|InvalidAuthorizationConsentException */
    public function process(DenyAuthorizationInputPort $input, DenyAuthorizationOutputPort $output): void;
}
