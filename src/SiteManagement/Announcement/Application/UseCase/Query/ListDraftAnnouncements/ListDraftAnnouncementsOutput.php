<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\ListDraftAnnouncements;

use Source\SiteManagement\Announcement\Application\UseCase\Query\DraftAnnouncementReadModel;

class ListDraftAnnouncementsOutput implements ListDraftAnnouncementsOutputPort
{
    /**
     * @var list<DraftAnnouncementReadModel>
     */
    private array $announcements = [];
    private int $currentPage = 1;
    private int $lastPage = 1;
    private int $total = 0;
    private int $perPage = 50;

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
    ): void {
        $this->announcements = $announcements;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
        $this->perPage = $perPage;
    }

    /**
     * @return array{announcements: list<array{announcementIdentifier: string, sourceLanguage: string, category: int, contents: array<string, array{title: string, content: string}>}>, current_page: int, last_page: int, total: int, per_page: int}
     */
    public function toArray(): array
    {
        return [
            'announcements' => array_map(static fn (DraftAnnouncementReadModel $announcement) => $announcement->toArray(), $this->announcements),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
            'per_page' => $this->perPage,
        ];
    }
}
