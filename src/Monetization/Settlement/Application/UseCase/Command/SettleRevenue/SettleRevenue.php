<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

use DateTimeImmutable;
use Source\Monetization\Settlement\Domain\Repository\SettlementBatchRepositoryInterface;
use Source\Monetization\Settlement\Domain\Repository\TransferRepositoryInterface;
use Source\Monetization\Settlement\Domain\Service\SettlementResult;
use Source\Monetization\Settlement\Domain\Service\SettlementServiceInterface;

readonly class SettleRevenue implements SettleRevenueInterface
{
    public function __construct(
        private SettlementServiceInterface $settlementService,
        private SettlementBatchRepositoryInterface $settlementBatchRepository,
        private TransferRepositoryInterface $transferRepository,
    ) {
    }

    public function process(SettleRevenueInputPort $input): SettlementResult
    {
        $result = $this->settlementService->settle(
            $input->settlementSchedule(),
            $input->paidAmounts(),
            $input->gatewayFeeRate(),
            $input->platformFeeRate(),
            $input->fixedFee(),
            $input->periodStart(),
            $input->periodEnd(),
            new DateTimeImmutable()
        );

        $this->settlementBatchRepository->save($result->batch());
        $this->transferRepository->save($result->transfer());

        return $result;
    }
}
