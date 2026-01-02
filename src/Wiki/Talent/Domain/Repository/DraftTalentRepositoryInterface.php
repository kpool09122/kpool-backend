<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Repository;

use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;

interface DraftTalentRepositoryInterface
{
    public function findById(TalentIdentifier $identifier): ?DraftTalent;

    public function save(DraftTalent $talent): void;

    public function delete(DraftTalent $talent): void;

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @return DraftTalent[]
     */
    public function findByTranslationSet(
        TranslationSetIdentifier $translationSetIdentifier,
    ): array;
}
