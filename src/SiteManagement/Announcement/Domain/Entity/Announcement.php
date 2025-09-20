<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Entity;

use Source\Shared\Domain\ValueObject\Translation;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

class Announcement
{
    public function __construct(
        private readonly AnnouncementIdentifier $announcementIdentifier,
        private readonly Translation $translation,
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

    public function translation(): Translation
    {
        return $this->translation;
    }

    public function category(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function title(): Title
    {
        return $this->title;
    }

    public function setTitle(Title $title): void
    {
        $this->title = $title;
    }

    public function content(): Content
    {
        return $this->content;
    }

    public function setContent(Content $content): void
    {
        $this->content = $content;
    }

    public function publishedDate(): PublishedDate
    {
        return $this->publishedDate;
    }

    public function setPublishedDate(PublishedDate $publishedDate): void
    {
        $this->publishedDate = $publishedDate;
    }
}
