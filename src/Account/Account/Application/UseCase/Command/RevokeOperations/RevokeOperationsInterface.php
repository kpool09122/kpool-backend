<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RevokeOperations;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Principal\Application\Exception\OperationsMembershipNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalGroupNotFoundException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;
use Source\Identity\Domain\Exception\IdentityNotFoundException;

interface RevokeOperationsInterface
{
    /**
     * @throws AccountNotFoundException
     * @throws SystemRoleNotFoundException
     * @throws PrincipalGroupNotFoundException
     * @throws PrincipalNotFoundException
     * @throws OperationsMembershipNotFoundException
     * @throws IdentityNotFoundException
     */
    public function process(RevokeOperationsInputPort $input, RevokeOperationsOutputPort $output): void;
}
