<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\UpdateAnnouncement;

use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\ValueObject\LocalizedAnnouncementContent;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

readonly class UpdateAnnouncement implements UpdateAnnouncementInterface
{
    public function __construct(
        private DraftAnnouncementRepositoryInterface $draftAnnouncementRepository,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @param UpdateAnnouncementInputPort $input
     * @throws AnnouncementNotFoundException
     * @throws UnauthorizedException
     */
    public function process(UpdateAnnouncementInputPort $input, UpdateAnnouncementOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::ANNOUNCEMENT_UPDATE, new Resource(ResourceType::ANNOUNCEMENT))) {
            throw new UnauthorizedException();
        }

        $announcement = $this->draftAnnouncementRepository->findById($input->announcementIdentifier());

        if ($announcement === null) {
            throw new AnnouncementNotFoundException();
        }

        $announcement->setCategory($input->category());
        $announcement->setLocalizedContent(new LocalizedAnnouncementContent($input->language(), $input->title(), $input->content()));
        $this->draftAnnouncementRepository->save($announcement);

        $output->setDraftAnnouncement($announcement);
    }
}
