<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use InvalidArgumentException;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface PublishAnnouncementInterface
{
    /**
     * @throws AnnouncementNotFoundException
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
     */
    public function process(PublishAnnouncementInputPort $input, PublishAnnouncementOutputPort $output): void;
}
