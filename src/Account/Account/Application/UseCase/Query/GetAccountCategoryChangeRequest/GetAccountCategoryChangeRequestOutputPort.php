<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetAccountCategoryChangeRequest;

use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestDetailReadModel;

interface GetAccountCategoryChangeRequestOutputPort
{
    public function output(AccountCategoryChangeRequestDetailReadModel $detail): void;
}
