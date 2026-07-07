<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Query\GetIdentityProfile;

use Source\Identity\Application\UseCase\Query\IdentityProfileReadModel;
use Source\Identity\Domain\Exception\IdentityNotFoundException;

interface GetIdentityProfileInterface
{
    /**
     * @throws IdentityNotFoundException
     */
    public function process(GetIdentityProfileInputPort $input): IdentityProfileReadModel;
}
