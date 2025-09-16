<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncements;

use Businesses\SiteManagement\Announcement\UseCase\Query\AnnouncementReadModel;

interface GetAnnouncementsInterface
{
    /**
     * @param GetAnnouncementsInputPort $input
     * @return list<AnnouncementReadModel>
     */
    public function process(GetAnnouncementsInputPort $input): array;
}
