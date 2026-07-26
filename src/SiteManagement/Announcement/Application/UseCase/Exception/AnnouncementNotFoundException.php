<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Exception;

use RuntimeException;
use Throwable;

class AnnouncementNotFoundException extends RuntimeException
{
    public function __construct(
        string $message = 'Announcement is not found.',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
