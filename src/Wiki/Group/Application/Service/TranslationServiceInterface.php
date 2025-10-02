<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Application\Service;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Entity\Group;

interface TranslationServiceInterface
{
    /**
     * 外部翻訳サービスを使ってGroupを翻訳しDraftGroupを作成
     *
     * @param Group $group
     * @param Translation $translation
     * @return DraftGroup
     */
    public function translateGroup(
        Group $group,
        Translation $translation,
    ): DraftGroup;
}
