<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\ApproveTalent;

use Source\Wiki\Group\Application\Exception\ExistsApprovedButNotTranslatedGroupException;
use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\ExistsApprovedButNotTranslatedTalentException;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\TalentServiceInterface;

class ApproveTalent implements ApproveTalentInterface
{
    public function __construct(
        private TalentRepositoryInterface $talentRepository,
        private TalentServiceInterface    $talentService,
    ) {
    }

    /**
     * @param ApproveTalentInputPort $input
     * @return DraftTalent
     * @throws TalentNotFoundException
     * @throws ExistsApprovedButNotTranslatedGroupException
     * @throws InvalidStatusException
     * @throws UnauthorizedException
     */
    public function process(ApproveTalentInputPort $input): DraftTalent
    {
        $talent = $this->talentRepository->findDraftById($input->talentIdentifier());

        if ($talent === null) {
            throw new TalentNotFoundException();
        }

        $principal = $input->principal();
        $groupIds = array_map(
            fn ($groupIdentifier) => (string) $groupIdentifier,
            $talent->groupIdentifiers()
        );
        // FIXME: agencyId も取得できるようにリファクタリングする
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::TALENT,
            agencyId: null,
            groupIds: $groupIds,
            talentId: (string) $talent->talentIdentifier(),
        );

        if (! $principal->role()->can(Action::APPROVE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        if ($talent->status() !== ApprovalStatus::UnderReview) {
            throw new InvalidStatusException();
        }

        // 同じ翻訳セットの別版で承認済みがあるかチェック
        if ($this->talentService->existsApprovedButNotTranslatedTalent(
            $talent->translationSetIdentifier(),
            $talent->talentIdentifier(),
        )) {
            throw new ExistsApprovedButNotTranslatedTalentException();
        }

        $talent->setStatus(ApprovalStatus::Approved);

        $this->talentRepository->saveDraft($talent);

        return $talent;
    }
}
