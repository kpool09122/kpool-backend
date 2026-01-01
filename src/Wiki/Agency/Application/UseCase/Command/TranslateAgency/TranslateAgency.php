<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Service\TranslationServiceInterface;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\Repository\DraftAgencyRepositoryInterface;
use Source\Wiki\Principal\Domain\Repository\PrincipalRepositoryInterface;
use Source\Wiki\Shared\Domain\Exception\PrincipalNotFoundException;
use Source\Wiki\Shared\Domain\Exception\UnauthorizedException;
use Source\Wiki\Shared\Domain\ValueObject\Action;
use Source\Wiki\Shared\Domain\ValueObject\ResourceIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ResourceType;

readonly class TranslateAgency implements TranslateAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface      $agencyRepository,
        private DraftAgencyRepositoryInterface $draftAgencyRepository,
        private TranslationServiceInterface    $translationService,
        private PrincipalRepositoryInterface   $principalRepository,
    ) {
    }

    /**
     * @param TranslateAgencyInputPort $input
     * @return DraftAgency[]
     * @throws AgencyNotFoundException
     * @throws UnauthorizedException
     * @throws PrincipalNotFoundException
     */
    public function process(TranslateAgencyInputPort $input): array
    {
        $agency = $this->agencyRepository->findById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        $principal = $this->principalRepository->findById($input->principalIdentifier());
        if ($principal === null) {
            throw new PrincipalNotFoundException();
        }

        $resourceIdentifier = new ResourceIdentifier(
            type: ResourceType::AGENCY,
            agencyId: (string) $agency->agencyIdentifier(),
            groupIds: [],
        );

        if (! $principal->role()->can(Action::TRANSLATE, $resourceIdentifier, $principal)) {
            throw new UnauthorizedException();
        }

        $languages = Language::allExcept($agency->language());

        $agencyDrafts = [];
        foreach ($languages as $language) {
            $agencyDraft = $this->translationService->translateAgency($agency, $language);
            $agencyDrafts[] = $agencyDraft;
            $this->draftAgencyRepository->save($agencyDraft);
        }

        return $agencyDrafts;
    }
}
