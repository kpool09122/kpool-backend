<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Command\CreateAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\Entity\Announcement;

interface CreateAnnouncementInterface
{
    public function process(CreateAnnouncementInputPort $input): Announcement;
}
