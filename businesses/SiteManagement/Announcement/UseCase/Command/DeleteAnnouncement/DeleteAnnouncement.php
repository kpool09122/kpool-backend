<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Command\DeleteAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\Entity\Announcement;
use Businesses\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Businesses\SiteManagement\Announcement\UseCase\Exception\AnnouncementNotFoundException;

class DeleteAnnouncement implements DeleteAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
    ) {
    }

    /**
     * @param DeleteAnnouncementInputPort $input
     * @return Announcement
     * @throws AnnouncementNotFoundException
     */
    public function process(DeleteAnnouncementInputPort $input): Announcement
    {
        $announcement = $this->announcementRepository->findById($input->announcementIdentifier());

        if ($announcement === null) {
            throw new AnnouncementNotFoundException();
        }

        $this->announcementRepository->delete($announcement);

        return $announcement;
    }
}
