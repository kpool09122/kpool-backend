<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements;

use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

interface ListDraftAnnouncementsInterface
{
    /** @throws UnauthorizedException */
    public function process(ListDraftAnnouncementsInputPort $input, ListDraftAnnouncementsOutputPort $output): void;
}
