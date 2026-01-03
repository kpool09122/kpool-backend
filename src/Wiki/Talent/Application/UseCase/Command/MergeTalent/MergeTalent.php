<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\MergeTalent;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Application\Exception\TalentNotFoundException;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;

readonly class MergeTalent implements MergeTalentInterface
{
    public function __construct(
        private DraftTalentRepositoryInterface $draftTalentRepository,
        private PrincipalRepositoryInterface   $principalRepository,
        private PolicyEvaluatorInterface       $policyEvaluator,
    ) {
    }

    /**
     * @param MergeTalentInputPort $input
     * @return DraftTalent
     * @throws TalentNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(MergeTalentInputPort $input): DraftTalent
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
        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::TALENT,
            agencyId: $talent->agencyIdentifier() ? (string) $talent->agencyIdentifier() : null,
            groupIds: $groupIds,
            talentIds: [(string) $talent->talentIdentifier()],
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::MERGE, $resourceIdentifier)) {
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
        $talent->setRelevantVideoLinks($input->relevantVideoLinks());
        $talent->setMergerIdentifier($input->principalIdentifier());
        $talent->setMergedAt($input->mergedAt());

        $this->draftTalentRepository->save($talent);

        return $talent;
    }
}
