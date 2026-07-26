<?php

declare(strict_types=1);

namespace Source\Monetization\Settlement\Domain\Exception;

use DomainException;
use Throwable;

class TransferGatewayException extends DomainException
{
    public function __construct(
        string $message = 'Transfer Gateway',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
