<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Exception;

use Exception;

class AnnouncementNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Announcement is not found.',
    ) {
        parent::__construct($message, 0);
    }
}
