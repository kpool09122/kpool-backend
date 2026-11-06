<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Domain\Exception;

use LogicException;
use Throwable;

class MetricDefinitionMismatchException extends LogicException
{
    public function __construct(
        string $message = 'Metric definition does not match the stored definition.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
