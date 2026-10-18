<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;

class DeleteAnnouncementOutput implements DeleteAnnouncementOutputPort
{
    /** @var DraftAnnouncement[] */
    private array $announcements = [];

    /** @param DraftAnnouncement[] $announcements */
    public function setAnnouncements(array $announcements): void
    {
        $this->announcements = $announcements;
    }

    /** @return DraftAnnouncement[] */
    public function announcements(): array
    {
        return $this->announcements;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['announcements' => array_map(static fn (DraftAnnouncement $announcement): array => [
            'announcementIdentifier' => (string) $announcement->announcementIdentifier(),
            'sourceLanguage' => $announcement->sourceLanguage()->value,
            'category' => $announcement->category()->value,
            'contents' => $announcement->contents()->toArray(),
            'publishedDate' => $announcement->publishedDate()->value()->format('Y-m-d'),
        ], $this->announcements)];
    }
}
