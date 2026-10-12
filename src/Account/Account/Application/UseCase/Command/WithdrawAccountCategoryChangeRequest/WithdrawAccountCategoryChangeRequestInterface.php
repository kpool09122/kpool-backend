<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\WithdrawAccountCategoryChangeRequest;

interface WithdrawAccountCategoryChangeRequestInterface
{
    public function process(WithdrawAccountCategoryChangeRequestInputPort $input, WithdrawAccountCategoryChangeRequestOutputPort $output): void;
}
