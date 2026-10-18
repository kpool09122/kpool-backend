<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;

class UpdateAnnouncementOutput implements UpdateAnnouncementOutputPort
{
    private ?DraftAnnouncement $draftAnnouncement = null;

    public function setDraftAnnouncement(DraftAnnouncement $draftAnnouncement): void
    {
        $this->draftAnnouncement = $draftAnnouncement;
    }

    public function draftAnnouncement(): ?DraftAnnouncement
    {
        return $this->draftAnnouncement;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        if ($this->draftAnnouncement === null) {
            return [];
        }
        $announcement = $this->draftAnnouncement;

        return [
            'announcementIdentifier' => (string) $announcement->announcementIdentifier(),
            'sourceLanguage' => $announcement->sourceLanguage()->value,
            'category' => $announcement->category()->value,
            'contents' => $announcement->contents()->toArray(),
            'publishedDate' => $announcement->publishedDate()->value()->format('Y-m-d'),
        ];
    }
}
