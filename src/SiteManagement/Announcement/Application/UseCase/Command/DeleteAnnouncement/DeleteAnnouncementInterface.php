<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface DeleteAnnouncementInterface
{
    /**
     * @throws UnauthorizedException
     */
    public function process(DeleteAnnouncementInputPort $input, DeleteAnnouncementOutputPort $output): void;
}
