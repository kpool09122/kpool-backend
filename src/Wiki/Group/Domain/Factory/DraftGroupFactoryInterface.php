<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

interface DraftGroupFactoryInterface
{
    /**
     * @param PrincipalIdentifier $editorIdentifier
     * @param Language $language
     * @param GroupName $name
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @return DraftGroup
     */
    public function create(
        PrincipalIdentifier       $editorIdentifier,
        Language                  $language,
        GroupName                 $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftGroup;
}
