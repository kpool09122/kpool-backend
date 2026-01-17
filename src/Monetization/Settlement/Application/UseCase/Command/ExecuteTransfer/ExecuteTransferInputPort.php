<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer;

use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;

interface ExecuteTransferInputPort
{
    public function transferIdentifier(): TransferIdentifier;
}
