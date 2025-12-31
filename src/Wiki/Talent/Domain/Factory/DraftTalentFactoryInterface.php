<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

interface DraftTalentFactoryInterface
{
    /**
     * @param PrincipalIdentifier $editorIdentifier
     * @param Language $language
     * @param TalentName $name
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @return DraftTalent
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        PrincipalIdentifier       $editorIdentifier,
        Language                  $language,
        TalentName                $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftTalent;
}
