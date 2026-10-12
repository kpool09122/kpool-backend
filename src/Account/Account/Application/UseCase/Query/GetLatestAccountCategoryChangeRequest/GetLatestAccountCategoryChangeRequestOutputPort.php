<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\GetLatestAccountCategoryChangeRequest;

use Source\Account\Account\Application\UseCase\Query\AccountCategoryChangeRequestReadModel;

interface GetLatestAccountCategoryChangeRequestOutputPort
{
    public function output(?AccountCategoryChangeRequestReadModel $request): void;
}
