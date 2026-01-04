<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SwitchIdentity;

use Source\Identity\Domain\Entity\Identity;
use Source\Identity\Domain\Exception\IdentityNotFoundException;

interface SwitchIdentityInterface
{
    /**
     * @param SwitchIdentityInputPort $input
     * @return Identity
     * @throws IdentityNotFoundException
     */
    public function process(SwitchIdentityInputPort $input): Identity;
}
