<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

interface DraftAnnouncementFactoryInterface
{
    /**
     * @param TranslationSetIdentifier|null $translationSetIdentifier
     * @param Translation $translation
     * @param Category $category
     * @param Title $title
     * @param Content $content
     * @param PublishedDate $publishedDate
     * @return DraftAnnouncement
     */
    public function create(
        ?TranslationSetIdentifier $translationSetIdentifier,
        Translation $translation,
        Category $category,
        Title $title,
        Content $content,
        PublishedDate $publishedDate,
    ): DraftAnnouncement;
}
