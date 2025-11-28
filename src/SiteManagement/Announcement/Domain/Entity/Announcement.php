<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Entity;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

readonly class Announcement
{
    public function __construct(
        private AnnouncementIdentifier   $announcementIdentifier,
        private TranslationSetIdentifier $translationSetIdentifier,
        private Language                 $language,
        private Category                 $category,
        private Title                    $title,
        private Content                  $content,
        private PublishedDate            $publishedDate,
    ) {
    }

    public function announcementIdentifier(): AnnouncementIdentifier
    {
        return $this->announcementIdentifier;
    }

    public function translationSetIdentifier(): TranslationSetIdentifier
    {
        return $this->translationSetIdentifier;
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

    public function publishedDate(): PublishedDate
    {
        return $this->publishedDate;
    }
}
