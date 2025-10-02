<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;

interface TranslateAnnouncementInterface
{
    /**
     * @param TranslateAnnouncementInputPort $input
     * @return DraftAnnouncement[]
     * @throws AnnouncementNotFoundException
     */
    public function process(TranslateAnnouncementInputPort $input): array;
}
