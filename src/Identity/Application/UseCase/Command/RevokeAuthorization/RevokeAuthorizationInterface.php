<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\RevokeAuthorization;

use Source\Identity\Application\Exception\AuthorizationConnectionNotFoundException;

interface RevokeAuthorizationInterface
{
    /** @throws AuthorizationConnectionNotFoundException */
    public function process(RevokeAuthorizationInputPort $input, RevokeAuthorizationOutputPort $output): void;
}
