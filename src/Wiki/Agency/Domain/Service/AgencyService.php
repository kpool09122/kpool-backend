<?php

declare(strict_types=1);

namespace Source\Wiki\Agency\Domain\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Agency\Domain\Repository\AgencyRepositoryInterface;
use Source\Wiki\Agency\Domain\ValueObject\AgencyIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;

class AgencyService implements AgencyServiceInterface
{
    public function __construct(
        private readonly AgencyRepositoryInterface $agencyRepository,
    ) {
    }

    public function existsApprovedButNotTranslatedAgency(
        TranslationSetIdentifier $translationSetIdentifier,
        AgencyIdentifier $excludeAgencyIdentifier,
    ): bool {
        $draftAgencies = $this->agencyRepository->findDraftsByTranslationSet(
            $translationSetIdentifier,
        );

        foreach ($draftAgencies as $draftAgency) {
            // 自分自身は除外
            if ((string) $draftAgency->agencyIdentifier() === (string) $excludeAgencyIdentifier) {
                continue;
            }

            // Approved状態のものが存在すればtrue
            if ($draftAgency->status() === ApprovalStatus::Approved) {
                return true;
            }
        }

        return false;
    }
}
