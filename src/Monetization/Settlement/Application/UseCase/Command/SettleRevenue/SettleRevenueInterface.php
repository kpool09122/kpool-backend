<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Application\UseCase\Command\SettleRevenue;

interface SettleRevenueInterface
{
    public function process(SettleRevenueInputPort $input, SettleRevenueOutputPort $output): void;
}
