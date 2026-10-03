<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;

interface TranslateAnnouncementOutputPort
{
    /** @param DraftAnnouncement[] $announcements */
    public function setAnnouncements(array $announcements): void;

    /** @return DraftAnnouncement[] */
    public function announcements(): array;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
