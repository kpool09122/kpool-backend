<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\CreateTalent;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;

class CreateTalent implements CreateTalentInterface
{
    public function __construct(
        private DraftTalentFactoryInterface $talentFactory,
        private TalentRepositoryInterface   $talentRepository,
        private ImageServiceInterface       $imageService,
    ) {
    }

    /**
     * @param CreateTalentInputPort $input
     * @return DraftTalent
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     */
    public function process(CreateTalentInputPort $input): DraftTalent
    {
        $principal = $input->principal();
        $groupIds = array_map(
            static fn ($groupIdentifier) => (string) $groupIdentifier,
            $input->groupIdentifiers()
        );
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::TALENT,
            agencyId: null,
            groupIds: $groupIds,
        );

        if (! $principal->role()->can(Action::CREATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $talent = $this->talentFactory->create(
            $input->editorIdentifier(),
            $input->language(),
            $input->name(),
        );
        if ($input->publishedTalentIdentifier()) {
            $publishedTalent = $this->talentRepository->findById($input->publishedTalentIdentifier());
            if ($publishedTalent) {
                $talent->setPublishedTalentIdentifier($publishedTalent->talentIdentifier());
            }
        }
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
