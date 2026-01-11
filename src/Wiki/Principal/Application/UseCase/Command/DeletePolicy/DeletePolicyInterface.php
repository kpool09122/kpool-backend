<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\DeletePolicy;

use Source\Wiki\Principal\Application\Exception\CannotDeleteSystemPolicyException;
use Source\Wiki\Principal\Application\Exception\PolicyNotFoundException;

interface DeletePolicyInterface
{
    /**
     * @throws PolicyNotFoundException
     * @throws CannotDeleteSystemPolicyException
     */
    public function process(DeletePolicyInputPort $input): void;
}
