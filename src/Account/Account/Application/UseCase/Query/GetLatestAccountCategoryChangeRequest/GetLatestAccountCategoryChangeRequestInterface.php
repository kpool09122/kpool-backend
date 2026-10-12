<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest;

use Source\Account\Account\Application\Exception\AccountCategoryChangeRequestForbiddenException;

interface GetLatestAccountCategoryChangeRequestInterface
{
    /** @throws AccountCategoryChangeRequestForbiddenException */
    public function process(GetLatestAccountCategoryChangeRequestInputPort $input, GetLatestAccountCategoryChangeRequestOutputPort $output): void;
}
