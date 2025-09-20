<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncements;

use Source\SiteManagement\Announcement\Application\UseCase\Query\AnnouncementReadModel;

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
