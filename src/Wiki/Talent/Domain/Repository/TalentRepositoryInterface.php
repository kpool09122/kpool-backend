<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Entity\Talent;

interface TalentRepositoryInterface
{
    public function findById(TalentIdentifier $identifier): ?Talent;

    public function save(Talent $talent): void;

    public function findDraftById(TalentIdentifier $identifier): ?DraftTalent;

    public function saveDraft(DraftTalent $talent): void;

    public function deleteDraft(DraftTalent $talent): void;

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return DraftTalent[]
     */
    public function findDraftsByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array;
}
