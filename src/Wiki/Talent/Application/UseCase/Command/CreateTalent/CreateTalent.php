<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Application\UseCase\Command\CreateTalent;

use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Principal\Domain\Service\PolicyEvaluatorInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\Resource;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactoryInterface;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;
use Source\Wiki\Talent\Domain\Repository\TalentRepositoryInterface;

readonly class CreateTalent implements CreateTalentInterface
{
    public function __construct(
        private DraftTalentFactoryInterface    $talentFactory,
        private TalentRepositoryInterface      $talentRepository,
        private DraftTalentRepositoryInterface $draftTalentRepository,
        private PrincipalRepositoryInterface   $principalRepository,
        private PolicyEvaluatorInterface       $policyEvaluator,
    ) {
    }

    /**
     * @param CreateTalentInputPort $input
     * @return DraftTalent
     * @throws ExceedMaxRelevantVideoLinksException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(CreateTalentInputPort $input): DraftTalent
    {
        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }
        $groupIds = array_map(
            static fn ($groupIdentifier) => (string) $groupIdentifier,
            $input->groupIdentifiers()
        );
        $resource = new Resource(
            type: ResourceType::TALENT,
            agencyId: null,
            groupIds: $groupIds,
        );

        if (! $this->policyEvaluator->evaluate($principal, Action::CREATE, $resource)) {
            throw new UnauthorizedException();
        }

        $talent = $this->talentFactory->create(
            $input->principalIdentifier(),
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
        $talent->setRelevantVideoLinks($input->relevantVideoLinks());

        $this->draftTalentRepository->save($talent);

        return $talent;
    }
}
