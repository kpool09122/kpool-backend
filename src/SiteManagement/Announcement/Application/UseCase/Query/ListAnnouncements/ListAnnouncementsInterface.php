<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements;

use Throwable;

interface ListAnnouncementsInterface
{
    /** @throws Throwable */
    public function process(ListAnnouncementsInputPort $input, ListAnnouncementsOutputPort $output): void;
}
