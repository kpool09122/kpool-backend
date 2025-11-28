<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってAnnouncementを翻訳しDraftAnnouncementを作成
     *
     * @param Announcement|DraftAnnouncement $announcement
     * @param Language $language
     * @return DraftAnnouncement
     */
    public function translateAnnouncement(
        Announcement|DraftAnnouncement $announcement,
        Language                       $language,
    ): DraftAnnouncement;
}
