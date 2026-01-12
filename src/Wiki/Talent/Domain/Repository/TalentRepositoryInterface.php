<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Repository;

use Source\Shared\Domain\ValueObject\AccountIdentifier;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\Entity\Talent;

interface TalentRepositoryInterface
{
    public function findById(TalentIdentifier $identifier): ?Talent;

    /**
     * @return Talent[]
     */
    public function findByTranslationSetIdentifier(TranslationSetIdentifier $translationSetIdentifier): array;

    /**
     * 指定されたAccountIdentifierをオーナーとする公式Talentを取得.
     */
    public function findByOwnerAccountId(AccountIdentifier $accountIdentifier): ?Talent;

    public function save(Talent $talent): void;
}
