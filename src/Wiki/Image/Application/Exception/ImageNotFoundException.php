<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Application\Exception;

use Exception;

class ImageNotFoundException extends Exception
{
    public function __construct(string $message = 'Image not found')
    {
        parent::__construct($message);
    }
}
