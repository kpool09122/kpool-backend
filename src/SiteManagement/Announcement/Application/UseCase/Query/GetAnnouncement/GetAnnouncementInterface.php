<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Application\UseCase\Query\AnnouncementReadModel;

interface GetAnnouncementInterface
{
    /** @throws AnnouncementNotFoundException */
    public function process(GetAnnouncementInputPort $input): AnnouncementReadModel;
}
