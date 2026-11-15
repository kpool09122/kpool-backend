<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\CreateAuthorizationRequest;

use Source\Identity\Application\Service\PendingAuthorization;

interface CreateAuthorizationRequestOutputPort
{
    public function setPending(PendingAuthorization $pending): void;
}
