<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncements;

use Source\SiteManagement\Announcement\Application\UseCase\Query\AnnouncementReadModel;

interface GetAnnouncementsInterface
{
    /**
     * @param GetAnnouncementsInputPort $input
     * @return list<AnnouncementReadModel>
     */
    public function process(GetAnnouncementsInputPort $input): array;
}
