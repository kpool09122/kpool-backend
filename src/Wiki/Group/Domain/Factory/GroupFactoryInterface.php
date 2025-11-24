<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\ValueObject\GroupName;

interface GroupFactoryInterface
{
    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Language $language
     * @param GroupName $name
     * @return Group
     */
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Language                 $language,
        GroupName                $name,
    ): Group;
}
