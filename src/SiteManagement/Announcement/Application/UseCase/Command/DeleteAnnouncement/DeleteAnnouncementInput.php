<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

readonly class DeleteAnnouncementInput implements DeleteAnnouncementInputPort
{
    public function __construct(
        private AnnouncementIdentifier $announcementIdentifier,
    ) {
    }

    public function announcementIdentifier(): AnnouncementIdentifier
    {
        return $this->announcementIdentifier;
    }
}
