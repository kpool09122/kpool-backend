<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;

interface DeleteAnnouncementOutputPort
{
    /** @param Announcement[] $announcements */
    public function setAnnouncements(array $announcements): void;

    /** @return Announcement[] */
    public function announcements(): array;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
