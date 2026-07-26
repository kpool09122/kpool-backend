<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Domain\Exception;

use DomainException;
use Throwable;

class CertificationNotPendingForRejectionException extends DomainException
{
    public function __construct(
        string $message = 'Only pending certifications can be rejected.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
