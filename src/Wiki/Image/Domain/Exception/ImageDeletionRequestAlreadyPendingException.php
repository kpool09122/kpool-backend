<?php

declare(strict_types=1);

namespace Source\Wiki\Image\Domain\Exception;

use DomainException;
use Throwable;

class ImageDeletionRequestAlreadyPendingException extends DomainException
{
    public function __construct(
        ?Throwable $previous = null,
    ) {
        parent::__construct('A pending deletion request already exists for this image.', 0, $previous);
    }
}
