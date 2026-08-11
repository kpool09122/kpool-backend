<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest;

interface GetAccountCategoryChangeRequestInterface
{
    public function process(GetAccountCategoryChangeRequestInputPort $input, GetAccountCategoryChangeRequestOutputPort $output): void;
}
