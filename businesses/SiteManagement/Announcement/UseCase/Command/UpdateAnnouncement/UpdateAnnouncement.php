<?php

namespace Businesses\SiteManagement\Announcement\UseCase\Command\UpdateAnnouncement;

use Businesses\SiteManagement\Announcement\Domain\Entity\Announcement;
use Businesses\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Businesses\SiteManagement\Announcement\UseCase\Exception\AnnouncementNotFoundException;

class UpdateAnnouncement implements UpdateAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
    ) {
    }

    /**
     * @param UpdateAnnouncementInputPort $input
     * @return Announcement
     * @throws AnnouncementNotFoundException
     */
    public function process(UpdateAnnouncementInputPort $input): Announcement
    {
        $announcement = $this->announcementRepository->findById($input->announcementIdentifier());

        if ($announcement === null) {
            throw new AnnouncementNotFoundException();
        }

        $announcement->setCategory($input->category());
        $announcement->setTitle($input->title());
        $announcement->setContent($input->content());
        $announcement->setPublishedDate($input->publishedDate());
        $this->announcementRepository->save($announcement);

        return $announcement;
    }
}
