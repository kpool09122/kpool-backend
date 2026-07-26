<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\Exception;

use RuntimeException;
use Throwable;

class OfficialCertificationAlreadyRequestedException extends RuntimeException
{
    public function __construct(
        string $message = 'Official Certification Already Requested',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
