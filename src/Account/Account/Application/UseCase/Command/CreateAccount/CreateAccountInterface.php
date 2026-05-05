<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\CreateAccount;

interface CreateAccountInterface
{
    /**
     * @param CreateAccountInputPort $input
     * @param CreateAccountOutputPort $output
     * @return void
     */
    public function process(CreateAccountInputPort $input, CreateAccountOutputPort $output): void;
}
