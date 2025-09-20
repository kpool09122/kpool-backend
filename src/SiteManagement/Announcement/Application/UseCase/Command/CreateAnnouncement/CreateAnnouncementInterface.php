<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;

interface CreateAnnouncementInterface
{
    public function process(CreateAnnouncementInputPort $input): Announcement;
}
