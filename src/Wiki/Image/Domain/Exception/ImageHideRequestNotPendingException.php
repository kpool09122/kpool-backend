<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Exception;

use DomainException;

class ImageHideRequestNotPendingException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Only pending requests can be approved or rejected.');
    }
}
