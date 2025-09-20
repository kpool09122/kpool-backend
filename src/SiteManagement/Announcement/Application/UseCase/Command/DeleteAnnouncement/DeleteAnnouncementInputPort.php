<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

interface DeleteAnnouncementInputPort
{
    public function announcementIdentifier(): AnnouncementIdentifier;
}
