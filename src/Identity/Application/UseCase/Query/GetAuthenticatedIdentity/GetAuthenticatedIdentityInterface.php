<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetAuthenticatedIdentity;

use Source\Identity\Application\UseCase\Query\AuthenticatedIdentityReadModel;
use Source\Identity\Domain\Exception\IdentityNotFoundException;

interface GetAuthenticatedIdentityInterface
{
    /**
     * @throws IdentityNotFoundException
     */
    public function process(GetAuthenticatedIdentityInputPort $input): AuthenticatedIdentityReadModel;
}
