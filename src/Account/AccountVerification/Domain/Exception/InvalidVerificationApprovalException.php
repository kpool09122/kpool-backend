<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\Exception;

use DomainException;

class InvalidVerificationApprovalException extends DomainException
{
    public function __construct(string $message = 'Only pending verifications can be approved.')
    {
        parent::__construct($message);
    }
}
