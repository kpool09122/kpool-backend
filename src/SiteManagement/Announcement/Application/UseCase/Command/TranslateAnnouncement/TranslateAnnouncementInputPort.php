<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

interface TranslateAnnouncementInputPort
{
    public function userIdentifier(): UserIdentifier;

    public function announcementIdentifier(): AnnouncementIdentifier;
}
