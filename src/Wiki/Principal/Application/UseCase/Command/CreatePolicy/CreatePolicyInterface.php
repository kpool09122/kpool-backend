<?php

declare(strict_types=1);

namespace Source\Wiki\Principal\Application\UseCase\Command\CreatePolicy;

interface CreatePolicyInterface
{
    public function process(CreatePolicyInputPort $input, CreatePolicyOutputPort $output): void;
}
