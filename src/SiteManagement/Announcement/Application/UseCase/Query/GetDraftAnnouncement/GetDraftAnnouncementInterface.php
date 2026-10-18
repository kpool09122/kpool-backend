<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Application\UseCase\Query\DraftAnnouncementReadModel;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface GetDraftAnnouncementInterface
{
    /**
     * @throws AnnouncementNotFoundException
     * @throws UnauthorizedException
     */
    public function process(GetDraftAnnouncementInputPort $input): DraftAnnouncementReadModel;
}
