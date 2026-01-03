<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount;

use Source\Monetization\Account\Application\Exception\MonetizationAccountAlreadyExistsException;
use Source\Monetization\Account\Domain\Entity\MonetizationAccount;

interface ProvisionMonetizationAccountInterface
{
    /**
     * @throws MonetizationAccountAlreadyExistsException
     */
    public function process(ProvisionMonetizationAccountInputPort $input): MonetizationAccount;
}
