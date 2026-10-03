<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;

interface PublishAnnouncementOutputPort
{
    /** @param Announcement[] $announcements */
    public function setAnnouncements(array $announcements): void;

    /** @return Announcement[] */
    public function announcements(): array;

    /** @return array<string, mixed> */
    public function toArray(): array;
}
