<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

class GetAnnouncementInput implements GetAnnouncementInputPort
{
    public function __construct(
        private AnnouncementIdentifier $announcementIdentifier
    ) {
    }

    public function announcementIdentifier(): AnnouncementIdentifier
    {
        return $this->announcementIdentifier;
    }
}
