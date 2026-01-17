<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\ExecuteTransfer;

use Source\Monetization\Settlement\Domain\ValueObject\TransferIdentifier;

readonly class ExecuteTransferInput implements ExecuteTransferInputPort
{
    public function __construct(
        private TransferIdentifier $transferIdentifier,
    ) {
    }

    public function transferIdentifier(): TransferIdentifier
    {
        return $this->transferIdentifier;
    }
}
