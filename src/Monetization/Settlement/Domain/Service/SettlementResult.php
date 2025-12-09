<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Service;

use Source\Monetization\Settlement\Domain\Entity\SettlementBatch;
use Source\Monetization\Settlement\Domain\Entity\Transfer;

readonly class SettlementResult
{
    public function __construct(
        private SettlementBatch $batch,
        private Transfer $transfer,
    ) {
    }

    public function batch(): SettlementBatch
    {
        return $this->batch;
    }

    public function transfer(): Transfer
    {
        return $this->transfer;
    }
}
