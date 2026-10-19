<?php

declare(strict_types=1);

namespace Source\SiteManagement\Announcement\Infrastructure\Query;

use Application\Models\SiteManagement\DraftAnnouncement as DraftAnnouncementModel;
use Source\SiteManagement\Announcement\Application\UseCase\Exception\AnnouncementNotFoundException;
use Source\SiteManagement\Announcement\Application\UseCase\Query\DraftAnnouncementReadModel;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement\GetDraftAnnouncementInputPort;
use Source\SiteManagement\Announcement\Application\UseCase\Query\GetDraftAnnouncement\GetDraftAnnouncementInterface;
use Source\SiteManagement\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\SiteManagement\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\SiteManagement\Principal\Domain\ValueObject\Action;
use Source\SiteManagement\Principal\Domain\ValueObject\Resource;
use Source\SiteManagement\Principal\Domain\ValueObject\ResourceType;
use Source\SiteManagement\Shared\Domain\Exception\UnauthorizedException;

readonly class GetDraftAnnouncement implements GetDraftAnnouncementInterface
{
    public function __construct(private PrincipalRepositoryInterface $principalRepository, private PolicyEvaluatorInterface $policyEvaluator)
    {
    }

    public function process(GetDraftAnnouncementInputPort $input): DraftAnnouncementReadModel
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null || ! $this->policyEvaluator->evaluate($principal, Action::ANNOUNCEMENT_VIEW_DRAFT, new Resource(ResourceType::ANNOUNCEMENT))) {
            throw new UnauthorizedException();
        }

        $model = DraftAnnouncementModel::query()->whereNull('deleted_at')->find((string) $input->announcementIdentifier());
        if ($model === null) {
            throw new AnnouncementNotFoundException();
        }

        return new DraftAnnouncementReadModel(
            $model->id,
            $model->source_language,
            $model->category,
            $model->contents,
        );
    }
}
