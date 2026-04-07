<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

use Source\Monetization\Settlement\Domain\Service\SettlementResult;

interface SettleRevenueOutputPort
{
    public function setResult(SettlementResult $result): void;
}
