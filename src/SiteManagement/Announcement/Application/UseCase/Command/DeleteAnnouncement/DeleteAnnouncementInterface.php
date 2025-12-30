<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface DeleteAnnouncementInterface
{
    /**
     * @return Announcement[]
     * @throws UnauthorizedException
     */
    public function process(DeleteAnnouncementInputPort $input): array;
}
