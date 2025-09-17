<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Command\UpdateAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Category;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Content;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Title;

readonly class UpdateAnnouncementInput implements UpdateAnnouncementInputPort
{
    public function __construct(
        private AnnouncementIdentifier $announcementIdentifier,
        private Category $category,
        private Title $title,
        private Content $content,
        private PublishedDate $publishedDate,
    ) {
    }

    public function announcementIdentifier(): AnnouncementIdentifier
    {
        return $this->announcementIdentifier;
    }

    public function category(): Category
    {
        return $this->category;
    }

    public function title(): Title
    {
        return $this->title;
    }

    public function content(): Content
    {
        return $this->content;
    }

    public function publishedDate(): PublishedDate
    {
        return $this->publishedDate;
    }
}
