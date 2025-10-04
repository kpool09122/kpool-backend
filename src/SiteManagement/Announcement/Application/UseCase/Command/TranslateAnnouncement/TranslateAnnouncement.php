<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\Shared\Domain\ValueObject\Translation;
use Source\SiteManagement\Announcement\Application\Service\TranslationServiceInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;

class TranslateAnnouncement implements TranslateAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
        private TranslationServiceInterface $translationService,
    ) {
    }

    /**
     * @param TranslateAnnouncementInputPort $input
     * @return DraftAnnouncement[]
     * @throws AnnouncementNotFoundException
     */
    public function process(TranslateAnnouncementInputPort $input): array
    {
        $announcement = $this->announcementRepository->findDraftById($input->announcementIdentifier());

        if ($announcement === null) {
            throw new AnnouncementNotFoundException();
        }

        $translations = Translation::allExcept($announcement->translation());

        $DraftAnnouncements = [];
        foreach ($translations as $translation) {
            // 外部翻訳サービスを使って翻訳
            $DraftAnnouncement = $this->translationService->translateAnnouncement($announcement, $translation);
            $DraftAnnouncements[] = $DraftAnnouncement;
            $this->announcementRepository->saveDraft($DraftAnnouncement);
        }

        return $DraftAnnouncements;
    }
}
