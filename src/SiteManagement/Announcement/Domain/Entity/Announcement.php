<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Entity;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementContents;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;

readonly class Announcement
{
    public function __construct(
        private AnnouncementIdentifier $announcementIdentifier,
        private Language $sourceLanguage,
        private Category $category,
        private AnnouncementContents $contents,
        private PublishedDate $publishedDate,
    ) {
        if ($contents->get($sourceLanguage) === null) {
            throw new InvalidArgumentException('Source language content is required.');
        }
    }

    public function announcementIdentifier(): AnnouncementIdentifier
    {
        return $this->announcementIdentifier;
    }

    public function sourceLanguage(): Language
    {
        return $this->sourceLanguage;
    }

    public function category(): Category
    {
        return $this->category;
    }

    public function contents(): AnnouncementContents
    {
        return $this->contents;
    }

    public function localizedContent(Language $language): ?LocalizedAnnouncementContent
    {
        return $this->contents->get($language);
    }

    public function publishedDate(): PublishedDate
    {
        return $this->publishedDate;
    }
}
