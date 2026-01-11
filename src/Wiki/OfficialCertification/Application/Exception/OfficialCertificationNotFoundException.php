<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\Exception;

use Exception;
use Throwable;

class OfficialCertificationNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Official Certification is not found.',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
