<?php

declare(strict_types=1);

namespace Source\Analyze\Principal\Application\UseCase\Query\GetCurrentPrincipal;

use Source\Analyze\Principal\Application\UseCase\Query\PrincipalReadModel;
use Source\Analyze\Shared\Domain\Exception\PrincipalNotFoundException;

interface GetCurrentPrincipalInterface
{
    /** @throws PrincipalNotFoundException */
    public function process(GetCurrentPrincipalInputPort $input): PrincipalReadModel;
}
