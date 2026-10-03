<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;

class DeleteAnnouncementOutput implements DeleteAnnouncementOutputPort
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
        return ['announcements' => array_map(
            static fn (Announcement $announcement): array => [
                'announcementIdentifier' => (string) $announcement->announcementIdentifier(),
                'translationSetIdentifier' => (string) $announcement->translationSetIdentifier(),
                'language' => $announcement->language()->value,
                'category' => $announcement->category()->value,
                'title' => (string) $announcement->title(),
                'content' => (string) $announcement->content(),
                'publishedDate' => $announcement->publishedDate()->value()->format('Y-m-d'),
            ],
            $this->announcements,
        )];
    }
}
