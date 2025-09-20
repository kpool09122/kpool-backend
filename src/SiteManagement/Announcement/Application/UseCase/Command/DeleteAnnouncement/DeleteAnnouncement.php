<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
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
