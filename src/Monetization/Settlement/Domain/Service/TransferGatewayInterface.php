<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Service;

use Source\Monetization\Account\Domain\Entity\MonetizationAccount;
use Source\Monetization\Settlement\Domain\Entity\Transfer;
use Source\Monetization\Settlement\Domain\Exception\TransferGatewayException;
use Source\Monetization\Settlement\Domain\ValueObject\StripeTransferId;

interface TransferGatewayInterface
{
    /**
     * 送金を実行
     *
     * @throws TransferGatewayException
     */
    public function execute(Transfer $transfer, MonetizationAccount $account): StripeTransferId;
}
