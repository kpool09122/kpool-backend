<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Query\GetCurrentPrincipal;

use Source\Wiki\Principal\Application\UseCase\Query\PrincipalReadModel;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;

interface GetCurrentPrincipalInterface
{
    /**
     * @throws PrincipalNotFoundException
     */
    public function process(GetCurrentPrincipalInputPort $input): PrincipalReadModel;
}
