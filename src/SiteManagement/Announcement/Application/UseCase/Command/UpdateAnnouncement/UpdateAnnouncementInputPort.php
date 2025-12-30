<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

interface UpdateAnnouncementInputPort
{
    public function userIdentifier(): UserIdentifier;

    public function announcementIdentifier(): AnnouncementIdentifier;

    public function category(): Category;

    public function title(): Title;

    public function content(): Content;

    public function publishedDate(): PublishedDate;
}
