<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

interface TranslateAnnouncementInputPort
{
    public function announcementIdentifier(): AnnouncementIdentifier;
}
