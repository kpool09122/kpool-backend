<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface GroupFactoryInterface
{
    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Translation $translation
     * @param GroupName $name
     * @return Group
     */
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Translation $translation,
        GroupName $name,
    ): Group;
}
