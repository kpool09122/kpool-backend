<?php

namespace Businesses\SiteManagement\Announcement\Domain\Repository;

use Businesses\SiteManagement\Announcement\Domain\Entity\Announcement;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

interface AnnouncementRepositoryInterface
{
    public function findById(AnnouncementIdentifier $announcementIdentifier): ?Announcement;

    public function save(Announcement $announcement): void;

    public function delete(Announcement $announcement): void;
}
