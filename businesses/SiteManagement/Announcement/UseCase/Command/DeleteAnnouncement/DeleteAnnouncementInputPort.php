<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Command\DeleteAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

interface DeleteAnnouncementInputPort
{
    public function announcementIdentifier(): AnnouncementIdentifier;
}
