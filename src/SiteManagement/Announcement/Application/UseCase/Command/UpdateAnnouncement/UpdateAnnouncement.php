<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;

class UpdateAnnouncement implements UpdateAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
    ) {
    }

    /**
     * @param UpdateAnnouncementInputPort $input
     * @return DraftAnnouncement
     * @throws AnnouncementNotFoundException
     */
    public function process(UpdateAnnouncementInputPort $input): DraftAnnouncement
    {
        $announcement = $this->announcementRepository->findDraftById($input->announcementIdentifier());

        if ($announcement === null) {
            throw new AnnouncementNotFoundException();
        }

        $announcement->setCategory($input->category());
        $announcement->setTitle($input->title());
        $announcement->setContent($input->content());
        $announcement->setPublishedDate($input->publishedDate());
        $this->announcementRepository->saveDraft($announcement);

        return $announcement;
    }
}
