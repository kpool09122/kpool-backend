<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\DeleteAnnouncement;

use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

class DeleteAnnouncement implements DeleteAnnouncementInterface
{
    public function __construct(
        private readonly DraftAnnouncementRepositoryInterface $draftAnnouncementRepository,
        private readonly PrincipalRepositoryInterface $principalRepository,
        private readonly PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws UnauthorizedException
     */
    public function process(DeleteAnnouncementInputPort $input, DeleteAnnouncementOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::ANNOUNCEMENT_DELETE, new Resource(ResourceType::ANNOUNCEMENT))) {
            throw new UnauthorizedException();
        }

        $draftAnnouncement = $this->draftAnnouncementRepository->findById($input->announcementIdentifier());
        if ($draftAnnouncement === null) {
            $output->setAnnouncements([]);

            return;
        }

        $this->draftAnnouncementRepository->delete($draftAnnouncement);
        $output->setAnnouncements([$draftAnnouncement]);
    }
}
