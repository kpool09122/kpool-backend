<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements;

use Source\SiteManagement\Announcement\Application\UseCase\Query\DraftAnnouncementReadModel;

interface ListDraftAnnouncementsOutputPort
{
    /**
     * @param list<DraftAnnouncementReadModel> $announcements
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
     * @return array{announcements: list<array{announcementIdentifier: string, sourceLanguage: string, category: int, contents: array<string, array{title: string, content: string}>}>, current_page: int, last_page: int, total: int, per_page: int}
     */
    public function toArray(): array;
}
