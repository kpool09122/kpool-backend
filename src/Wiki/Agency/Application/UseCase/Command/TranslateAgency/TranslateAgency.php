<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Service\TranslationServiceInterface;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;

class TranslateAgency implements TranslateAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
        private TranslationServiceInterface $translationService,
    ) {
    }

    /**
     * @param TranslateAgencyInputPort $input
     * @return DraftAgency[]
     * @throws AgencyNotFoundException
     */
    public function process(TranslateAgencyInputPort $input): array
    {
        $agency = $this->agencyRepository->findById($input->agencyIdentifier());

        if ($agency === null) {
            throw new AgencyNotFoundException();
        }

        $translations = Translation::allExcept($agency->translation());

        $agencyDrafts = [];
        foreach ($translations as $translation) {
            $agencyDraft = $this->translationService->translateAgency($agency, $translation);
            $agencyDrafts[] = $agencyDraft;
            $this->agencyRepository->saveDraft($agencyDraft);
        }

        return $agencyDrafts;
    }
}
