<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;

interface DeleteAnnouncementInterface
{
    /**
     * @param DeleteAnnouncementInputPort $input
     * @return Announcement
     * @throws AnnouncementNotFoundException
     */
    public function process(DeleteAnnouncementInputPort $input): Announcement;
}
