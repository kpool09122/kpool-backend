<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

readonly class PublishAnnouncement implements PublishAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
        private AnnouncementFactoryInterface $announcementFactory,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @throws UnauthorizedException
     */
    public function process(PublishAnnouncementInputPort $input, PublishAnnouncementOutputPort $output): void
    {
        $user = $this->userRepository->findById($input->userIdentifier());
        if (! $user?->isAdmin()) {
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
