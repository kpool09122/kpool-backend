<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Exception;

use DomainException;
use Throwable;

class ImageDeletionRequestNotPendingException extends DomainException
{
    public function __construct(
        ?Throwable $previous = null,
    ) {
        parent::__construct('Only pending requests can be approved or rejected.', 0, $previous);
    }
}
