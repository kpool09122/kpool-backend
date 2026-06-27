<?php

declare(strict_types=1);

namespace Source\Account\IdentityGroup\Application\UseCase\Command\DeleteIdentityGroup;

use Source\Account\IdentityGroup\Application\Exception\CannotDeleteDefaultIdentityGroupException;
use Source\Account\IdentityGroup\Application\Exception\CannotDeleteLastOwnerGroupException;
use Source\Account\IdentityGroup\Application\Exception\IdentityGroupNotFoundException;

interface DeleteIdentityGroupInterface
{
    /**
     * @throws CannotDeleteDefaultIdentityGroupException
     * @throws CannotDeleteLastOwnerGroupException
     * @throws IdentityGroupNotFoundException
     */
    public function process(DeleteIdentityGroupInputPort $input): void;
}
