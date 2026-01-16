<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\EditTalent;

use Source\Shared\Application\Service\ImageServiceInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;

readonly class EditTalent implements EditTalentInterface
{
    public function __construct(
        private DraftTalentRepositoryInterface $draftTalentRepository,
        private NormalizationServiceInterface  $normalizationService,
        private ImageServiceInterface          $imageService,
        private PrincipalRepositoryInterface   $principalRepository,
        private PolicyEvaluatorInterface       $policyEvaluator,
    ) {
    }

    /**
     * @param EditTalentInputPort $input
     * @return DraftTalent
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(EditTalentInputPort $input): DraftTalent
    {
        $talent = $this->draftTalentRepository->findById($input->talentIdentifier());

        if ($talent === null) {
            throw new TalentNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $groupIds = array_map(
            static fn ($groupIdentifier) => (string) $groupIdentifier,
            $talent->groupIdentifiers()
        );
        $resource = new Resource(
            type: ResourceType::TALENT,
            agencyId: (string) $talent->agencyIdentifier(),
            groupIds: $groupIds,
            talentIds: [(string) $talent->talentIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::EDIT, $resource)) {
            throw new UnauthorizedException();
        }

        $talent->setName($input->name());
        $talent->setRealName($input->realName());
        $talent->setNormalizedName(
            $this->normalizationService->normalize((string) $talent->name(), $talent->language())
        );
        $talent->setNormalizedRealName(
            $this->normalizationService->normalize((string) $talent->realName(), $talent->language())
        );
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

        $this->draftTalentRepository->save($talent);

        return $talent;
    }
}
