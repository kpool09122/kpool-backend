<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncement;

use Businesses\SiteManagement\Announcement\UseCase\Query\AnnouncementReadModel;

interface GetAnnouncementInterface
{
    public function process(GetAnnouncementInputPort $input): AnnouncementReadModel;
}
