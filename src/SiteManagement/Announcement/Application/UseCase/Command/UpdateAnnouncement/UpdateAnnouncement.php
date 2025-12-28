<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Entity\DraftAnnouncement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

readonly class UpdateAnnouncement implements UpdateAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @param UpdateAnnouncementInputPort $input
     * @return DraftAnnouncement
     * @throws AnnouncementNotFoundException
     * @throws UnauthorizedException
     */
    public function process(UpdateAnnouncementInputPort $input): DraftAnnouncement
    {
        $user = $this->userRepository->findById($input->userIdentifier());
        if (! $user?->isAdmin()) {
            throw new UnauthorizedException();
        }

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
