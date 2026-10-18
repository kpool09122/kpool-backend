<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;

interface TranslationServiceInterface
{
    public function translateAnnouncement(LocalizedAnnouncementContent $source, Language $language): LocalizedAnnouncementContent;
}
