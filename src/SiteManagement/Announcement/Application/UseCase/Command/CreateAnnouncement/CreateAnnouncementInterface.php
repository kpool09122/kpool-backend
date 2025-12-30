<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface CreateAnnouncementInterface
{
    /**
     * @throws UnauthorizedException
     */
    public function process(CreateAnnouncementInputPort $input): DraftAnnouncement;
}
