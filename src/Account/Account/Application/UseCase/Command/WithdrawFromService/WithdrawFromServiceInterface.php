<?php

declare(strict_types=1);

namespace Source\Account\Account\Application\UseCase\Command\WithdrawFromService;

use Source\Account\Account\Domain\Exception\IdentityWithdrawalNotAllowedException;

interface WithdrawFromServiceInterface
{
    /** @throws IdentityWithdrawalNotAllowedException */
    public function process(WithdrawFromServiceInputPort $input, WithdrawFromServiceOutputPort $output): void;
}
