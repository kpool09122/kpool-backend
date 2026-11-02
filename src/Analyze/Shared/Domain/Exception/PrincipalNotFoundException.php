<?php

declare(strict_types=1);

namespace Source\Analyze\Shared\Domain\Exception;

use Exception;
use Throwable;

class PrincipalNotFoundException extends Exception
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Analyze principal not found.', 0, $previous);
    }
}
