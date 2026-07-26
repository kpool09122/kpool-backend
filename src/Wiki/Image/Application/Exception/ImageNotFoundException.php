<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\Exception;

use RuntimeException;
use Throwable;

class ImageNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'Image not found',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
