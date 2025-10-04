<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;

class DeleteAnnouncement implements DeleteAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
    ) {
    }

    /**
     * @param DeleteAnnouncementInputPort $input
     * @return Announcement[]
     */
    public function process(DeleteAnnouncementInputPort $input): array
    {
        $announcements = $this->announcementRepository->findByTranslationSetIdentifier($input->translationSetIdentifier());

        $deletedAnnouncements = [];
        foreach ($announcements as $announcement) {
            $this->announcementRepository->delete($announcement);
            $deletedAnnouncements[] = $announcement;
        }

        return $deletedAnnouncements;
    }
}
