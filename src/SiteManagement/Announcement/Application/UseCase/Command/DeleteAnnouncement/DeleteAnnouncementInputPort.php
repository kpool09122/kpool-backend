<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface DeleteAnnouncementInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function announcementIdentifier(): AnnouncementIdentifier;
}
