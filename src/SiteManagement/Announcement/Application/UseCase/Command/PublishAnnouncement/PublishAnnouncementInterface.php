<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface PublishAnnouncementInterface
{
    /**
     * @throws UnauthorizedException
     */
    public function process(PublishAnnouncementInputPort $input, PublishAnnouncementOutputPort $output): void;
}
