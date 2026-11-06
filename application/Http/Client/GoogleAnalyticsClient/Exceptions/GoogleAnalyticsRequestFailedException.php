<?php

declare(strict_types=1);

namespace Application\Http\Client\GoogleAnalyticsClient\Exceptions;

use RuntimeException;

class GoogleAnalyticsRequestFailedException extends RuntimeException
{
    public function __construct(string $message = 'Google Analytics data could not be retrieved completely.')
    {
        parent::__construct($message);
    }
}
