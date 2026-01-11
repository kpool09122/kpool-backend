<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

use Source\Wiki\Principal\Domain\Entity\Policy;

interface CreatePolicyInterface
{
    public function process(CreatePolicyInputPort $input): Policy;
}
