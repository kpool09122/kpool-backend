<?php

declare(strict_types=1);

namespace Source\Analyze\App\Application\Exception;

use Exception;
use Throwable;

class MetricsFetchFailedException extends Exception
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Daily metrics fetch failed.', 0, $previous);
    }
}
