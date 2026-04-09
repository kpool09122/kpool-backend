<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\SwitchIdentity;

use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Identity\Domain\Exception\InvalidDelegationException;

interface SwitchIdentityInterface
{
    /**
     * @param SwitchIdentityInputPort $input
     * @param SwitchIdentityOutputPort $output
     * @return void
     * @throws IdentityNotFoundException
     * @throws InvalidDelegationException
     */
    public function process(SwitchIdentityInputPort $input, SwitchIdentityOutputPort $output): void;
}
