<?php

declare(strict_types=1);

namespace Source\SiteManagement\User\Application\UseCase\Command\ProvisionUser;

use Source\SiteManagement\User\Domain\Exception\AlreadyUserExistsException;

interface ProvisionUserInterface
{
    /**
     * @param ProvisionUserInputPort $inputPort
     * @throws AlreadyUserExistsException
     */
    public function process(ProvisionUserInputPort $inputPort, ProvisionUserOutputPort $output): void;
}
