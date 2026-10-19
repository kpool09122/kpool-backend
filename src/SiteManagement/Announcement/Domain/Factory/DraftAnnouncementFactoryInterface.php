<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\ValueObject\Category;
use Source\SiteManagement\Announcement\Domain\ValueObject\Content;
use Source\SiteManagement\Announcement\Domain\ValueObject\Title;

interface DraftAnnouncementFactoryInterface
{
    public function create(Language $language, Category $category, Title $title, Content $content): DraftAnnouncement;
}
