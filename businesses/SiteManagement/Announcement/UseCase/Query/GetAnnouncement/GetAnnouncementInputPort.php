<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncement;

use Businesses\Shared\ValueObject\Translation;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

interface GetAnnouncementInputPort
{
    public function announcementIdentifier(): AnnouncementIdentifier;

    public function translation(): Translation;
}
