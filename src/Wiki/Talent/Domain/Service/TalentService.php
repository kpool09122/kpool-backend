<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Service;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\Repository\DraftTalentRepositoryInterface;

class TalentService implements TalentServiceInterface
{
    public function __construct(
        private readonly DraftTalentRepositoryInterface $draftTalentRepository,
    ) {
    }

    public function existsApprovedButNotTranslatedTalent(
        TranslationSetIdentifier $translationSetIdentifier,
        TalentIdentifier         $excludeTalentIdentifier,
    ): bool {
        $draftTalents = $this->draftTalentRepository->findByTranslationSet(
            $translationSetIdentifier,
        );

        foreach ($draftTalents as $draftTalent) {
            // 自分自身は除外
            if ((string) $draftTalent->TalentIdentifier() === (string) $excludeTalentIdentifier) {
                continue;
            }

            // Approved状態のものが存在すればtrue
            if ($draftTalent->status() === ApprovalStatus::Approved) {
                return true;
            }
        }

        return false;
    }
}
