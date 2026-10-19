<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Infrastructure\Factory;

use Carbon\CarbonImmutable;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;

readonly class AnnouncementFactory implements AnnouncementFactoryInterface
{
    public function create(DraftAnnouncement $draftAnnouncement): Announcement
    {
        return new Announcement($draftAnnouncement->announcementIdentifier(), $draftAnnouncement->sourceLanguage(), $draftAnnouncement->category(), $draftAnnouncement->contents(), new PublishedDate(CarbonImmutable::now()));
    }
}
