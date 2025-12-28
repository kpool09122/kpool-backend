<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface UpdateAnnouncementInterface
{
    /**
     * @param UpdateAnnouncementInputPort $input
     * @return DraftAnnouncement
     * @throws AnnouncementNotFoundException
     * @throws UnauthorizedException
     */
    public function process(UpdateAnnouncementInputPort $input): DraftAnnouncement;
}
