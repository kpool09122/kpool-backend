<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\ApproveAccountCategoryChangeRequest;

interface ApproveAccountCategoryChangeRequestInterface
{
    public function process(ApproveAccountCategoryChangeRequestInputPort $input, ApproveAccountCategoryChangeRequestOutputPort $output): void;
}
