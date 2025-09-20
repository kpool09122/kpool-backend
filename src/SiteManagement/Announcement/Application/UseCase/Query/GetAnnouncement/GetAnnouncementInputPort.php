<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncement;

use Source\Shared\Domain\ValueObject\Translation;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

interface GetAnnouncementInputPort
{
    public function announcementIdentifier(): AnnouncementIdentifier;

    public function translation(): Translation;
}
