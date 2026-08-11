<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Query\ListAccountCategoryChangeRequests;

interface ListAccountCategoryChangeRequestsInterface
{
    public function process(ListAccountCategoryChangeRequestsInputPort $input, ListAccountCategoryChangeRequestsOutputPort $output): void;
}
