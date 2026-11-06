<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\Exception;

use LogicException;
use Throwable;

class EmptyReportingPeriodException extends LogicException
{
    public function __construct(
        string $message = 'Reporting period is empty.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
