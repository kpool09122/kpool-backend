<?php

declare(strict_types=1);

namespace Source\Wiki\OfficialCertification\Application\Exception;

use LogicException;
use Throwable;

class OfficialCertificationOwnerAccountTypeMissingException extends LogicException
{
    public function __construct(
        string $message = 'Active certification owner account must have a type.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
