<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountTypeChange;

interface RequestAccountTypeChangeInterface
{
    public function process(RequestAccountTypeChangeInputPort $input, RequestAccountTypeChangeOutputPort $output): void;
}
