<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Source\SiteManagement\User\Domain\ValueObject\UserIdentifier;

readonly class UpdateAnnouncementInput implements UpdateAnnouncementInputPort
{
    public function __construct(
        private UserIdentifier         $userIdentifier,
        private AnnouncementIdentifier $announcementIdentifier,
        private Category               $category,
        private Title                  $title,
        private Content                $content,
        private PublishedDate          $publishedDate,
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
