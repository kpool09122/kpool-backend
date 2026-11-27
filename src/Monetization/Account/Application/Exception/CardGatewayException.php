<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\Exception;

use RuntimeException;
use Throwable;

class CardGatewayException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('CardGatewayException', 0, $previous);
    }
}
