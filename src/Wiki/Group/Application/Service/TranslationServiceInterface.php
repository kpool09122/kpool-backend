<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\Service;

use Source\Shared\Domain\ValueObject\Language;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\Group;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってGroupを翻訳しDraftGroupを作成
     *
     * @param Group $group
     * @param Language $language
     * @return DraftGroup
     */
    public function translateGroup(
        Group    $group,
        Language $language,
    ): DraftGroup;
}
