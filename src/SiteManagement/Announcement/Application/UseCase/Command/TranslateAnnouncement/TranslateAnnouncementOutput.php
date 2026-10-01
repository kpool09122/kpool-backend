<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;

class TranslateAnnouncementOutput implements TranslateAnnouncementOutputPort
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
        return ['announcements' => array_map(
            static fn (DraftAnnouncement $announcement): array => [
                'announcementIdentifier' => (string) $announcement->announcementIdentifier(),
                'translationSetIdentifier' => (string) $announcement->translationSetIdentifier(),
                'language' => $announcement->translation()->value,
                'category' => $announcement->category()->value,
                'title' => (string) $announcement->title(),
                'content' => (string) $announcement->content(),
                'publishedDate' => $announcement->publishedDate()->value()->format('Y-m-d'),
            ],
            $this->announcements,
        )];
    }
}
