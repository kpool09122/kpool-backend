<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

interface AnnouncementFactoryInterface
{
    public function create(
        Translation $translation,
        Category $category,
        Title $title,
        Content $content,
        PublishedDate $publishedDate,
    ): Announcement;
}
