<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

use DateTimeImmutable;
use Source\Monetization\Settlement\Domain\Service\SettlementResult;
use Source\Monetization\Settlement\Domain\Service\SettlementServiceInterface;

readonly class SettleRevenue implements SettleRevenueInterface
{
    public function __construct(
        private SettlementServiceInterface $settlementService,
    ) {
    }

    public function process(SettleRevenueInputPort $input): SettlementResult
    {
        return $this->settlementService->settle(
            $input->settlementAccount(),
            $input->settlementSchedule(),
            $input->paidAmounts(),
            $input->gatewayFeeRate(),
            $input->platformFeeRate(),
            $input->fixedFee(),
            $input->periodStart(),
            $input->periodEnd(),
            new DateTimeImmutable()
        );
    }
}
