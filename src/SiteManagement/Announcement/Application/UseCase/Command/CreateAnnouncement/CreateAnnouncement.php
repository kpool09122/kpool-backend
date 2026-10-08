<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\CreateAnnouncement;

use Source\SiteManagement\Announcement\Domain\Factory\DraftAnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

readonly class CreateAnnouncement implements CreateAnnouncementInterface
{
    public function __construct(
        private DraftAnnouncementFactoryInterface $draftAnnouncementFactory,
        private AnnouncementRepositoryInterface   $announcementRepository,
        private PrincipalRepositoryInterface           $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    public function process(CreateAnnouncementInputPort $input, CreateAnnouncementOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::ANNOUNCEMENT_CREATE, new Resource(ResourceType::ANNOUNCEMENT))) {
            throw new UnauthorizedException();
        }

        $draftAnnouncement = $this->draftAnnouncementFactory->create(
            $input->translationSetIdentifier(),
            $input->language(),
            $input->category(),
            $input->title(),
            $input->content(),
            $input->publishedDate(),
        );

        $this->announcementRepository->saveDraft($draftAnnouncement);

        $output->setDraftAnnouncement($draftAnnouncement);
    }
}
