<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RevokeOperations;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;

interface RevokeOperationsInterface
{
    /**
     * @throws AccountNotFoundException
     * @throws SystemRoleNotFoundException
     * @throws PrincipalGroupNotFoundException
     */
    public function process(RevokeOperationsInputPort $input, RevokeOperationsOutputPort $output): void;
}
