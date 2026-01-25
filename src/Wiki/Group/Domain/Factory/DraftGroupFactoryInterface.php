<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;

interface DraftGroupFactoryInterface
{
    /**
     * @param PrincipalIdentifier $editorIdentifier
     * @param Language $language
     * @param GroupName $name
     * @param Slug $slug
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @return DraftGroup
     */
    public function create(
        PrincipalIdentifier       $editorIdentifier,
        Language                  $language,
        GroupName                 $name,
        Slug                      $slug,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftGroup;
}
