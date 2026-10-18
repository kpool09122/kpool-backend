<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Factory;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;

interface AnnouncementFactoryInterface
{
    public function create(DraftAnnouncement $draftAnnouncement): Announcement;
}
