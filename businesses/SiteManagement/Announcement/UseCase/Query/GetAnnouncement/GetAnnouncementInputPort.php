<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

interface GetAnnouncementInputPort
{
    public function announcementIdentifier(): AnnouncementIdentifier;
}
