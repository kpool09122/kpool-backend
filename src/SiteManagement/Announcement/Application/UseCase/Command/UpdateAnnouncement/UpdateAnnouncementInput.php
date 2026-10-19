<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;
use Source\SiteManagement\Principal\Domain\ValueObject\PrincipalIdentifier;

readonly class UpdateAnnouncementInput implements UpdateAnnouncementInputPort
{
    public function __construct(
        private PrincipalIdentifier         $principalIdentifier,
        private AnnouncementIdentifier $announcementIdentifier,
        private Language $language,
        private Category               $category,
        private Title                  $title,
        private Content                $content,
    ) {
    }

    public function principalIdentifier(): PrincipalIdentifier
    {
        return $this->principalIdentifier;
    }

    public function announcementIdentifier(): AnnouncementIdentifier
    {
        return $this->announcementIdentifier;
    }

    public function language(): Language
    {
        return $this->language;
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
}
