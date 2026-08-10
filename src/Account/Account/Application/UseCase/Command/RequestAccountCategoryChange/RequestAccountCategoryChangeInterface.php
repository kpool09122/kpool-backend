<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\RequestAccountCategoryChange;

interface RequestAccountCategoryChangeInterface
{
    public function process(RequestAccountCategoryChangeInputPort $input, RequestAccountCategoryChangeOutputPort $output): void;
}
