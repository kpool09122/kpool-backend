<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Repository;

use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

interface DraftAnnouncementRepositoryInterface
{
    public function findById(AnnouncementIdentifier $announcementIdentifier): ?DraftAnnouncement;

    public function save(DraftAnnouncement $draftAnnouncement): void;

    public function delete(DraftAnnouncement $draftAnnouncement): void;
}
