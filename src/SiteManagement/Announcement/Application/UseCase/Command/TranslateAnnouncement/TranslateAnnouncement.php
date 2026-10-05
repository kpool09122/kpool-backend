<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\Service\TranslationServiceInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

readonly class TranslateAnnouncement implements TranslateAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
        private TranslationServiceInterface     $translationService,
        private PrincipalRepositoryInterface         $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @param TranslateAnnouncementInputPort $input
     * @throws AnnouncementNotFoundException
     * @throws UnauthorizedException
     */
    public function process(TranslateAnnouncementInputPort $input, TranslateAnnouncementOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::ANNOUNCEMENT_TRANSLATE, new Resource(ResourceType::ANNOUNCEMENT))) {
            throw new UnauthorizedException();
        }

        $announcement = $this->announcementRepository->findDraftById($input->announcementIdentifier());

        if ($announcement === null) {
            throw new AnnouncementNotFoundException();
        }

        $languages = Language::allExcept($announcement->translation());

        $draftAnnouncements = [];
        foreach ($languages as $language) {
            // 外部翻訳サービスを使って翻訳
            $draftAnnouncement = $this->translationService->translateAnnouncement($announcement, $language);
            $draftAnnouncements[] = $draftAnnouncement;
            $this->announcementRepository->saveDraft($draftAnnouncement);
        }

        $output->setAnnouncements($draftAnnouncements);
    }
}
