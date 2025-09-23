<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Application\UseCase\Command\TranslateAgency;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Agency\Application\Exception\AgencyNotFoundException;
use Source\Wiki\Agency\Application\Service\AgencyServiceInterface;
use Source\Wiki\Agency\Domain\Entity\DraftAgency;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;

class TranslateAgency implements TranslateAgencyInterface
{
    public function __construct(
        private AgencyRepositoryInterface $agencyRepository,
        private AgencyServiceInterface $agencyService,
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
            $agencyDraft = $this->agencyService->translateAgency($agency, $translation);
            $agencyDrafts[] = $agencyDraft;
            $this->agencyRepository->saveDraft($agencyDraft);
        }

        return $agencyDrafts;
    }
}
