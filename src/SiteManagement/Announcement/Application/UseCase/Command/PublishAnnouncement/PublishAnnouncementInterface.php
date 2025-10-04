<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;

interface PublishAnnouncementInterface
{
    /**
     * @param PublishAnnouncementInputPort $input
     * @return Announcement[]
     */
    public function process(PublishAnnouncementInputPort $input): array;
}
