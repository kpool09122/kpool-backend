<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\ValueObject\AnnouncementIdentifier;

interface AnnouncementRepositoryInterface
{
    public function findById(AnnouncementIdentifier $announcementIdentifier): ?Announcement;

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return Announcement[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array;

    public function findDraftById(AnnouncementIdentifier $announcementIdentifier): ?DraftAnnouncement;

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return DraftAnnouncement[]
     */
    public function findDraftsByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array;

    public function save(Announcement $announcement): void;

    public function saveDraft(DraftAnnouncement $draftAnnouncement): void;

    public function delete(Announcement $announcement): void;

    public function deleteDraft(DraftAnnouncement $draftAnnouncement): void;
}
