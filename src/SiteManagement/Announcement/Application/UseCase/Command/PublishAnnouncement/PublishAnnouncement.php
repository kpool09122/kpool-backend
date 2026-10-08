<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

readonly class PublishAnnouncement implements PublishAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
        private AnnouncementFactoryInterface $announcementFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws UnauthorizedException
     */
    public function process(PublishAnnouncementInputPort $input, PublishAnnouncementOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::ANNOUNCEMENT_PUBLISH, new Resource(ResourceType::ANNOUNCEMENT))) {
            throw new UnauthorizedException();
        }

        $announcements = $this->announcementRepository->findDraftsByTranslationSetIdentifier($input->translationSetIdentifier());

        $publishedAnnouncements = [];
        foreach ($announcements as $announcement) {
            $publishedAnnouncement = $this->announcementFactory->create(
                $announcement->translationSetIdentifier(),
                $announcement->translation(),
                $announcement->category(),
                $announcement->title(),
                $announcement->content(),
                $announcement->publishedDate(),
            );

            $this->announcementRepository->save($publishedAnnouncement);
            $publishedAnnouncements[] = $publishedAnnouncement;
            $this->announcementRepository->deleteDraft($announcement);
        }

        $output->setAnnouncements($publishedAnnouncements);
    }
}
