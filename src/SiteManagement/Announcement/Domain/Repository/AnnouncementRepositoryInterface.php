<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Repository;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

interface AnnouncementRepositoryInterface
{
    public function findById(AnnouncementIdentifier $announcementIdentifier): ?Announcement;

    public function save(Announcement $announcement): void;

    public function delete(Announcement $announcement): void;
}
