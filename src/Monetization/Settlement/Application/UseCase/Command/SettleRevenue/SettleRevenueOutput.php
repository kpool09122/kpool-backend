<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

use DateTimeInterface;
use Source\Monetization\Settlement\Domain\Service\SettlementResult;

class SettleRevenueOutput implements SettleRevenueOutputPort
{
    private ?SettlementResult $result = null;

    public function setResult(SettlementResult $result): void
    {
        $this->result = $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->result === null) {
            return [];
        }

        $result = $this->result;

        return [
            'settlementBatchIdentifier' => (string) $result->batch()->settlementBatchIdentifier(),
            'monetizationAccountIdentifier' => (string) $result->batch()->monetizationAccountIdentifier(),
            'currency' => $result->batch()->currency()->value,
            'grossAmount' => $result->batch()->grossAmount()->amount(),
            'feeAmount' => $result->batch()->feeAmount()->amount(),
            'netAmount' => $result->batch()->netAmount()->amount(),
            'status' => $result->batch()->status()->value,
            'periodStart' => $result->batch()->periodStart()->format(DateTimeInterface::ATOM),
            'periodEnd' => $result->batch()->periodEnd()->format(DateTimeInterface::ATOM),
            'transferIdentifier' => (string) $result->transfer()->transferIdentifier(),
            'transferStatus' => $result->transfer()->status()->value,
        ];
    }
}
