<?php

declare(strict_types=1);

namespace Businesses\SiteManagement\Announcement\Domain\Factory;

use Businesses\Shared\ValueObject\Translation;
use Businesses\SiteManagement\Announcement\Domain\Entity\Announcement;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Category;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Content;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\PublishedDate;
use Businesses\SiteManagement\Announcement\Domain\ValueObject\Title;

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
