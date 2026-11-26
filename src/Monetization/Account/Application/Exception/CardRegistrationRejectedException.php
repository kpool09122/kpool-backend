<?php

declare(strict_types=1);

namespace Source\Monetization\Account\Application\Exception;

use RuntimeException;
use Throwable;

class CardRegistrationRejectedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('CardRegistrationRejectedException', 0, $previous);
    }
}
