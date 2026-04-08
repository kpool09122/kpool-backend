<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount;

use Source\Monetization\Account\Application\Exception\MonetizationAccountAlreadyExistsException;

interface ProvisionMonetizationAccountInterface
{
    /**
     * @param ProvisionMonetizationAccountInputPort $input
     * @param ProvisionMonetizationAccountOutputPort $output
     * @return void
     * @throws MonetizationAccountAlreadyExistsException
     */
    public function process(ProvisionMonetizationAccountInputPort $input, ProvisionMonetizationAccountOutputPort $output): void;
}
