<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Query\GetAnnouncement;

use Businesses\Shared\ValueObject\Translation;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

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
