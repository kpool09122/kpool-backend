<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\ProvisionMonetizationAccount;

use Source\Monetization\Account\Domain\Entity\MonetizationAccount;

interface ProvisionMonetizationAccountOutputPort
{
    public function setMonetizationAccount(MonetizationAccount $monetizationAccount): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
