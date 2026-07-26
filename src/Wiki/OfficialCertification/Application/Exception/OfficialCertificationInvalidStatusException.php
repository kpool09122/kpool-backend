<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\Exception;

use RuntimeException;
use Throwable;

class OfficialCertificationInvalidStatusException extends RuntimeException
{
    public function __construct(
        string $message = 'Official Certification Invalid Status',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
