<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Member\Domain\Entity\DraftMember;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Member\Domain\ValueObject\MemberName;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

interface DraftMemberFactoryInterface
{
    /**
     * @param EditorIdentifier $editorIdentifier
     * @param Translation $translation
     * @param MemberName $name
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @return DraftMember
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        EditorIdentifier $editorIdentifier,
        Translation $translation,
        MemberName $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftMember;
}
