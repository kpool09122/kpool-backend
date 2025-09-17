<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Command\UpdateAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\Entity\Announcement;
use Businesses\SiteManagement\Announcement\UseCase\Exception\AnnouncementNotFoundException;

interface UpdateAnnouncementInterface
{
    /**
     * @param UpdateAnnouncementInputPort $input
     * @return Announcement
     * @throws AnnouncementNotFoundException
     */
    public function process(UpdateAnnouncementInputPort $input): Announcement;
}
