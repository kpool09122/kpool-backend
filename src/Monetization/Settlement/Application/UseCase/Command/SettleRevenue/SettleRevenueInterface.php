<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

use Source\Monetization\Settlement\Domain\Service\SettlementResult;

interface SettleRevenueInterface
{
    public function process(SettleRevenueInputPort $input): SettlementResult;
}
