<?php

declare(strict_types=1);

namespace Source\Account\Principal\Application\UseCase\Command\CreatePrincipalGroup;

interface CreatePrincipalGroupInterface
{
    public function process(CreatePrincipalGroupInputPort $input, CreatePrincipalGroupOutputPort $output): void;
}
