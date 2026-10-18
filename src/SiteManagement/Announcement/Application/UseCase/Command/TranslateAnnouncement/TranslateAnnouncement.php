<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\TranslateAnnouncement;

use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\Service\TranslationServiceInterface;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use UnexpectedValueException;

readonly class TranslateAnnouncement implements TranslateAnnouncementInterface
{
    public function __construct(
        private DraftAnnouncementRepositoryInterface $draftAnnouncementRepository,
        private TranslationServiceInterface $translationService,
        private PrincipalRepositoryInterface $principalRepository,
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

        $announcement = $this->draftAnnouncementRepository->findById($input->announcementIdentifier());

        if ($announcement === null) {
            throw new AnnouncementNotFoundException();
        }

        $source = $announcement->localizedContent($announcement->sourceLanguage());
        if ($source === null) {
            throw new AnnouncementNotFoundException();
        }
        $contents = $announcement->contents();
        foreach (Language::allExcept($announcement->sourceLanguage()) as $language) {
            if ($contents->get($language) !== null) {
                continue;
            }
            $translated = $this->translationService->translateAnnouncement($source, $language);
            if ($translated->language !== $language) {
                throw new UnexpectedValueException('Translation returned an unexpected language.');
            }
            $contents = $contents->with($translated);
        }
        foreach (Language::cases() as $language) {
            $content = $contents->get($language);
            if ($content !== null) {
                $announcement->setLocalizedContent($content);
            }
        }
        $this->draftAnnouncementRepository->save($announcement);
        $output->setAnnouncements([$announcement]);
    }
}
