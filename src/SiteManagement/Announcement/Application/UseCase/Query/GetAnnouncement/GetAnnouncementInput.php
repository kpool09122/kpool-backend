<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Query\GetAnnouncement;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

readonly class GetAnnouncementInput implements GetAnnouncementInputPort
{
    public function __construct(
        private AnnouncementIdentifier $announcementIdentifier,
        private Language               $language,
    ) {
    }

    public function announcementIdentifier(): AnnouncementIdentifier
    {
        return $this->announcementIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
    }
}
