<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

readonly class TranslateAnnouncementInput implements TranslateAnnouncementInputPort
{
    public function __construct(
        private UserIdentifier         $userIdentifier,
        private AnnouncementIdentifier $announcementIdentifier,
    ) {
    }

    public function userIdentifier(): UserIdentifier
    {
        return $this->userIdentifier;
    }

    public function announcementIdentifier(): AnnouncementIdentifier
    {
        return $this->announcementIdentifier;
    }
}
