<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\ListAnnouncements;

use Source\SiteManagement\Announcement\Application\UseCase\Query\AnnouncementReadModel;

interface ListAnnouncementsOutputPort
{
    /**
     * @param list<AnnouncementReadModel> $announcements
     * @param int $currentPage
     * @param int $lastPage
     * @param int $total
     * @param int $perPage
     * @return void
     */
    public function output(
        array $announcements,
        int $currentPage,
        int $lastPage,
        int $total,
        int $perPage,
    ): void;

    /**
     * @return array{announcements: list<array<string, mixed>>, current_page: int, last_page: int, total: int, per_page: int}
     */
    public function toArray(): array;
}
