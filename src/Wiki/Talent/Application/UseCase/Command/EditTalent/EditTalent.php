<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\EditTalent;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;

readonly class EditTalent implements EditTalentInterface
{
    public function __construct(
        private TalentRepositoryInterface $talentRepository,
        private ImageServiceInterface     $imageService,
    ) {
    }

    /**
     * @param EditTalentInputPort $input
     * @return DraftTalent
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     */
    public function process(EditTalentInputPort $input): DraftTalent
    {
        $talent = $this->talentRepository->findDraftById($input->talentIdentifier());

        if ($talent === null) {
            throw new TalentNotFoundException();
        }

        $principal = $input->principal();
        $groupIds = array_map(
            static fn ($groupIdentifier) => (string) $groupIdentifier,
            $talent->groupIdentifiers()
        );
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::TALENT,
            agencyId: (string) $talent->agencyIdentifier(),
            groupIds: $groupIds,
            talentId: (string) $talent->talentIdentifier(),
        );

        if (! $principal->role()->can(Action::EDIT, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $talent->setName($input->name());
        $talent->setRealName($input->realName());
        if ($input->agencyIdentifier()) {
            $talent->setAgencyIdentifier($input->agencyIdentifier());
        }
        $talent->setGroupIdentifiers($input->groupIdentifiers());
        $talent->setBirthday($input->birthday());
        $talent->setCareer($input->career());
        if ($input->base64EncodedImage()) {
            $imageLink = $this->imageService->upload($input->base64EncodedImage());
            $talent->setImageLink($imageLink);
        }
        $talent->setRelevantVideoLinks($input->relevantVideoLinks());

        $this->talentRepository->saveDraft($talent);

        return $talent;
    }
}
