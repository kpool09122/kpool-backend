<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface TranslateAnnouncementInterface
{
    /**
     * @param TranslateAnnouncementInputPort $input
     * @return DraftAnnouncement[]
     * @throws AnnouncementNotFoundException
     * @throws UnauthorizedException
     */
    public function process(TranslateAnnouncementInputPort $input): array;
}
