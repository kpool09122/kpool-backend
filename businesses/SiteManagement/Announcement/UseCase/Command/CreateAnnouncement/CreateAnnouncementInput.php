<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\UseCase\Command\CreateAnnouncement;

use Businesses\Shared\ValueObject\Translation;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Category;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Content;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Title;

readonly class CreateAnnouncementInput implements CreateAnnouncementInputPort
{
    /**
     * @param Category $category
     * @param Title $title
     * @param Content $content
     * @param PublishedDate $publishedDate
     */
    public function __construct(
        private Translation $translation,
        private Category $category,
        private Title $title,
        private Content $content,
        private PublishedDate $publishedDate,
    ) {
    }

    public function translation(): Translation
    {
        return $this->translation;
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
