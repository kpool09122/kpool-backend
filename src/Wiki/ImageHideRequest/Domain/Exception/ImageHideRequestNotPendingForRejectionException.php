<?php

declare(strict_types=1);

namespace Source\Wiki\ImageHideRequest\Domain\Exception;

use DomainException;

class ImageHideRequestNotPendingForRejectionException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Only pending requests can be rejected.');
    }
}
