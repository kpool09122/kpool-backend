<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement;

use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

interface GetDraftAnnouncementInputPort
{
    public function principalIdentifier(): PrincipalIdentifier;

    public function announcementIdentifier(): AnnouncementIdentifier;
}
