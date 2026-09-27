<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\GrantOperations;

use Source\Account\Account\Application\Exception\AccountNotFoundException;
use Source\Account\Account\Application\Exception\EmailNotVerifiedException;
use Source\Account\Principal\Application\Exception\PrincipalNotFoundException;
use Source\Account\Principal\Domain\Exception\SystemRoleNotFoundException;
use Source\Identity\Domain\Exception\IdentityNotFoundException;

interface GrantOperationsInterface
{
    /**
     * @throws AccountNotFoundException
     * @throws PrincipalNotFoundException
     * @throws SystemRoleNotFoundException
     * @throws IdentityNotFoundException
     * @throws EmailNotVerifiedException
     */
    public function process(GrantOperationsInputPort $input, GrantOperationsOutputPort $output): void;
}
