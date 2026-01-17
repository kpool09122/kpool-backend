<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer;

use Source\Monetization\Account\Domain\Exception\MonetizationAccountNotFoundException;
use Source\Monetization\Settlement\Domain\Exception\TransferNotFoundException;

interface ExecuteTransferInterface
{
    /**
     * @param ExecuteTransferInputPort $input
     * @return void
     * @throws TransferNotFoundException
     * @throws MonetizationAccountNotFoundException
     */
    public function process(ExecuteTransferInputPort $input): void;
}
