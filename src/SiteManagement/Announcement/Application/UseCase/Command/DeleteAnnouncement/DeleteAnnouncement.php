<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\Entity\Announcement;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;
use Source\SiteManagement\User\Domain\Repository\UserRepositoryInterface;

class DeleteAnnouncement implements DeleteAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return Announcement[]
     * @throws UnauthorizedException
     */
    public function process(DeleteAnnouncementInputPort $input): array
    {
        $user = $this->userRepository->findById($input->userIdentifier());
        if (! $user?->isAdmin()) {
            throw new UnauthorizedException();
        }

        $announcements = $this->announcementRepository->findByTranslationSetIdentifier($input->translationSetIdentifier());

        $deletedAnnouncements = [];
        foreach ($announcements as $announcement) {
            $this->announcementRepository->delete($announcement);
            $deletedAnnouncements[] = $announcement;
        }

        return $deletedAnnouncements;
    }
}
