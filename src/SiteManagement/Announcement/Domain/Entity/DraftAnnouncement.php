<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Entity;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementContents;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;

class DraftAnnouncement
{
    public function __construct(
        private readonly AnnouncementIdentifier $announcementIdentifier,
        private readonly Language $sourceLanguage,
        private Category $category,
        private AnnouncementContents $contents,
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

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function setLocalizedContent(LocalizedAnnouncementContent $content): void
    {
        $this->contents = $this->contents->with($content);
    }
}
