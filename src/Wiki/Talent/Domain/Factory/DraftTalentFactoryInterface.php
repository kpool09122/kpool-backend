<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

interface DraftTalentFactoryInterface
{
    /**
     * @param EditorIdentifier $editorIdentifier
     * @param Translation $translation
     * @param TalentName $name
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @return DraftTalent
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        EditorIdentifier $editorIdentifier,
        Translation $translation,
        TalentName $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftTalent;
}
