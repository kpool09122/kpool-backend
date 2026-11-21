<?php

declare(strict_types=1);

namespace Source\Monetization\Principal\Application\UseCase\Query\GetCurrentPrincipal;

use Source\Monetization\Principal\Application\UseCase\Query\PrincipalReadModel;
use Source\Monetization\Shared\Domain\Exception\PrincipalNotFoundException;

interface GetCurrentPrincipalInterface
{
    /** @throws PrincipalNotFoundException */
    public function process(GetCurrentPrincipalInputPort $input): PrincipalReadModel;
}
