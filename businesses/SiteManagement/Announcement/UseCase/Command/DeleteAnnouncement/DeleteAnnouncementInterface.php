<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Command\DeleteAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\Entity\Announcement;
use Businesses\SiteManagement\Announcement\UseCase\Exception\AnnouncementNotFoundException;

interface DeleteAnnouncementInterface
{
    /**
     * @param DeleteAnnouncementInputPort $input
     * @return Announcement
     * @throws AnnouncementNotFoundException
     */
    public function process(DeleteAnnouncementInputPort $input): Announcement;
}
