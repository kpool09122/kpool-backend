<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Domain\Exception;

use DomainException;

class CertificationNotPendingForRejectionException extends DomainException
{
    public function __construct(
        string $message = 'Only pending certifications can be rejected.',
    ) {
        parent::__construct($message, 0);
    }
}
