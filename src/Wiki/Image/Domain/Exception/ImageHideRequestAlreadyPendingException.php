<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Exception;

use DomainException;

class ImageHideRequestAlreadyPendingException extends DomainException
{
    public function __construct()
    {
        parent::__construct('A pending hide request already exists for this image.');
    }
}
