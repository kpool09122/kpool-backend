<?php

declare(strict_types=1);

namespace Source\Identity\Application\UseCase\Command\UpdateIdentity;

use Source\Identity\Domain\Exception\IdentityNotFoundException;
use Source\Shared\Application\Exception\InvalidBase64ImageException;

interface UpdateIdentityInterface
{
    /**
     * @throws IdentityNotFoundException
     * @throws InvalidBase64ImageException
     */
    public function process(UpdateIdentityInputPort $input, UpdateIdentityOutputPort $output): void;
}
