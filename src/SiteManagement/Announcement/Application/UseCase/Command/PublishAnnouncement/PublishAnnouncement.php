<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Application\UseCase\Command\PublishAnnouncement;

use InvalidArgumentException;
use Source\Shared\Domain\ValueObject\Language;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Domain\Factory\AnnouncementFactoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\AnnouncementRepositoryInterface;
use Source\SiteManagement\Announcement\Domain\Repository\DraftAnnouncementRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

readonly class PublishAnnouncement implements PublishAnnouncementInterface
{
    public function __construct(
        private AnnouncementRepositoryInterface $announcementRepository,
        private DraftAnnouncementRepositoryInterface $draftAnnouncementRepository,
        private AnnouncementFactoryInterface $announcementFactory,
        private PrincipalRepositoryInterface $principalRepository,
        private PolicyEvaluatorInterface $policyEvaluator,
    ) {
    }

    /**
     * @throws AnnouncementNotFoundException
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
     */
    public function process(PublishAnnouncementInputPort $input, PublishAnnouncementOutputPort $output): void
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::ANNOUNCEMENT_PUBLISH, new Resource(ResourceType::ANNOUNCEMENT))) {
            throw new UnauthorizedException();
        }

        $draftAnnouncement = $this->draftAnnouncementRepository->findById($input->announcementIdentifier());
        if ($draftAnnouncement === null) {
            throw new AnnouncementNotFoundException();
        }

        foreach (Language::cases() as $language) {
            if ($draftAnnouncement->localizedContent($language) === null) {
                throw new InvalidArgumentException('All announcement languages require a non-empty title and content.');
            }
        }

        $announcement = $this->announcementFactory->create($draftAnnouncement);
        $this->announcementRepository->save($announcement);
        $this->draftAnnouncementRepository->delete($draftAnnouncement);
        $output->setAnnouncements([$announcement]);
    }
}
