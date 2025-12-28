<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\Service\TranslationServiceInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

readonly class TranslateAnnouncement implements TranslateAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
        private TranslationServiceInterface     $translationService,
        private UserRepositoryInterface         $userRepository,
    ) {
    }

    /**
     * @param TranslateAnnouncementInputPort $input
     * @return DraftAnnouncement[]
     * @throws AnnouncementNotFoundException
     * @throws UnauthorizedException
     */
    public function process(TranslateAnnouncementInputPort $input): array
    {
        $user = $this->userRepository->findById($input->userIdentifier());
        if (! $user?->isAdmin()) {
            throw new UnauthorizedException();
        }

        $announcement = $this->announcementRepository->findDraftById($input->announcementIdentifier());

        if ($announcement === null) {
            throw new AnnouncementNotFoundException();
        }

        $languages = Language::allExcept($announcement->translation());

        $DraftAnnouncements = [];
        foreach ($languages as $language) {
            // 外部翻訳サービスを使って翻訳
            $DraftAnnouncement = $this->translationService->translateAnnouncement($announcement, $language);
            $DraftAnnouncements[] = $DraftAnnouncement;
            $this->announcementRepository->saveDraft($DraftAnnouncement);
        }

        return $DraftAnnouncements;
    }
}
