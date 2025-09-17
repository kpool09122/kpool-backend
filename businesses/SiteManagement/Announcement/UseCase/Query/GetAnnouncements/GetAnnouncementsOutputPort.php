<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncements;

use Businesses\SiteManagement\Announcement\UseCase\Query\AnnouncementReadModel;

interface GetAnnouncementsOutputPort
{
    /**
     * @param AnnouncementReadModel[] $announcements
     * @param int $currentPage
     * @param int $lastPage
     * @param int $total
     * @return void
     */
    public function output(
        array $announcements,
        int $currentPage,
        int $lastPage,
        int $total,
    ): void;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
