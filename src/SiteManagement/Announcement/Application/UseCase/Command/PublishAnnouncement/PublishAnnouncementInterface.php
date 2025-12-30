<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface PublishAnnouncementInterface
{
    /**
     * @return Announcement[]
     * @throws UnauthorizedException
     */
    public function process(PublishAnnouncementInputPort $input): array;
}
