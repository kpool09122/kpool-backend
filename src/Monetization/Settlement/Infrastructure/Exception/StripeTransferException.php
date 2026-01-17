<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Infrastructure\Exception;

use Source\Monetization\Settlement\Domain\Exception\TransferGatewayException;

class StripeTransferException extends TransferGatewayException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
