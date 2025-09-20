<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;

interface UpdateAnnouncementInterface
{
    /**
     * @param UpdateAnnouncementInputPort $input
     * @return Announcement
     * @throws AnnouncementNotFoundException
     */
    public function process(UpdateAnnouncementInputPort $input): Announcement;
}
