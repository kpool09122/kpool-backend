<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;

class PublishAnnouncementOutput implements PublishAnnouncementOutputPort
{
    /** @var Announcement[] */
    private array $announcements = [];

    /** @param Announcement[] $announcements */
    public function setAnnouncements(array $announcements): void
    {
        $this->announcements = $announcements;
    }

    /** @return Announcement[] */
    public function announcements(): array
    {
        return $this->announcements;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['announcements' => array_map(static fn (Announcement $announcement): array => [
            'announcementIdentifier' => (string) $announcement->announcementIdentifier(),
            'sourceLanguage' => $announcement->sourceLanguage()->value,
            'category' => $announcement->category()->value,
            'contents' => $announcement->contents()->toArray(),
            'publishedDate' => $announcement->publishedDate()->value()->format('Y-m-d'),
        ], $this->announcements)];
    }
}
