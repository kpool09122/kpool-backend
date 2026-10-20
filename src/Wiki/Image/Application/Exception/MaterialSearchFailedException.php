<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\Exception;

use RuntimeException;
use Throwable;

final class MaterialSearchFailedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Material search is temporarily unavailable.', 0, $previous);
    }
}
