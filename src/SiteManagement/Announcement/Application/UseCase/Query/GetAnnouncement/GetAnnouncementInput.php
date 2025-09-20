<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncement;

use Source\Shared\Domain\ValueObject\Translation;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

class GetAnnouncementInput implements GetAnnouncementInputPort
{
    public function __construct(
        private AnnouncementIdentifier $announcementIdentifier,
        private Translation $translation,
    ) {
    }

    public function announcementIdentifier(): AnnouncementIdentifier
    {
        return $this->announcementIdentifier;
    }

    public function translation(): Translation
    {
        return $this->translation;
    }
}
