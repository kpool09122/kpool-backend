<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\UseCase\Command\SyncPayoutAccount;

use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;

interface SyncPayoutAccountInterface
{
    /**
     * @throws MonetizationAccountNotFoundException
     */
    public function process(SyncPayoutAccountInputPort $input): void;
}
