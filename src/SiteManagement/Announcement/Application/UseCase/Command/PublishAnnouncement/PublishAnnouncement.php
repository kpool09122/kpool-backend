<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;

readonly class PublishAnnouncement implements PublishAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
        private AnnouncementFactoryInterface $announcementFactory,
    ) {
    }

    /**
     * @param PublishAnnouncementInputPort $input
     * @return Announcement[]
     */
    public function process(PublishAnnouncementInputPort $input): array
    {
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

        return $publishedAnnouncements;
    }
}
