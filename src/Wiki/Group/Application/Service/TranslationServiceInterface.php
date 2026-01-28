<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Domain\Entity\Group;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってGroupを翻訳しTranslatedGroupDataを返す
     *
     * @param Group $group
     * @param Language $targetLanguage
     * @return TranslatedGroupData
     */
    public function translateGroup(
        Group    $group,
        Language $targetLanguage,
    ): TranslatedGroupData;
}
