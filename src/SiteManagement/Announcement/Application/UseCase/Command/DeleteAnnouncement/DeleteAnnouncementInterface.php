<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;

interface DeleteAnnouncementInterface
{
    /**
     * @param DeleteAnnouncementInputPort $input
     * @return Announcement[]
     */
    public function process(DeleteAnnouncementInputPort $input): array;
}
