<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Domain\Exception;

use DomainException;

class CertificationNotPendingForApprovalException extends DomainException
{
    public function __construct(
        string $message = 'Only pending certifications can be approved.',
    ) {
        parent::__construct($message, 0);
    }
}
