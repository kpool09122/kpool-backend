<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\PublishTalent;

use Source\Wiki\Shared\Domain\Exception\InvalidStatusException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\ExistsApprovedButNotTranslatedTalentException;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\TalentFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Service\TalentServiceInterface;

class PublishTalent implements PublishTalentInterface
{
    public function __construct(
        private TalentRepositoryInterface $talentRepository,
        private TalentServiceInterface    $talentService,
        private TalentFactoryInterface    $talentFactory,
    ) {
    }

    /**
     * @param PublishTalentInputPort $input
     * @return Talent
     * @throws TalentNotFoundException
     * @throws InvalidStatusException
     * @throws ExistsApprovedButNotTranslatedTalentException
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function process(PublishTalentInputPort $input): Talent
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
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::TALENT,
            agencyId: null,
            groupIds: $groupIds,
            talentId: (string) $talent->talentIdentifier(),
        );

        if (! $principal->role()->can(Action::PUBLISH, $resourceIdentifier, $principal)) {
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

        if ($talent->publishedTalentIdentifier()) {
            $publishedTalent = $this->talentRepository->findById($input->publishedTalentIdentifier());
            if ($publishedTalent === null) {
                throw new TalentNotFoundException();
            }
            $publishedTalent->setName($talent->name());
        } else {
            $publishedTalent = $this->talentFactory->create(
                $talent->translationSetIdentifier(),
                $talent->translation(),
                $talent->name(),
            );
        }
        $publishedTalent->setRealName($talent->realName());
        $publishedTalent->setGroupIdentifiers($talent->groupIdentifiers());
        $publishedTalent->setBirthday($talent->birthday());
        $publishedTalent->setCareer($talent->career());
        $publishedTalent->setImageLink($talent->imageLink());
        $publishedTalent->setRelevantVideoLinks($talent->relevantVideoLinks());

        $this->talentRepository->save($publishedTalent);
        $this->talentRepository->deleteDraft($talent);

        return $publishedTalent;
    }
}
