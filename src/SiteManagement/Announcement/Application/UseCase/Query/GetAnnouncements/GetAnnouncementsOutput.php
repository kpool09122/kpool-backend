<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncements;

use Source\SiteManagement\Announcement\Application\UseCase\Query\AnnouncementReadModel;

class GetAnnouncementsOutput implements GetAnnouncementsOutputPort
{
    /**
     * @var AnnouncementReadModel[]
     */
    private array $announcements = [];
    private ?int $currentPage = null;
    private ?int $lastPage = null;
    private ?int $total = null;

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
    ): void {
        $this->announcements = $announcements;
        $this->currentPage = $currentPage;
        $this->lastPage = $lastPage;
        $this->total = $total;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'announcements' => array_map(static fn (AnnouncementReadModel $announcement) => $announcement->toArray(), $this->announcements),
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage,
            'total' => $this->total,
        ];
    }
}
