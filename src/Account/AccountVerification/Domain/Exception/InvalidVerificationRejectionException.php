<?php

declare(strict_types=1);

namespace Source\Account\AccountVerification\Domain\Exception;

use DomainException;

class InvalidVerificationRejectionException extends DomainException
{
    public function __construct(string $message = 'Only pending verifications can be rejected.')
    {
        parent::__construct($message);
    }
}
