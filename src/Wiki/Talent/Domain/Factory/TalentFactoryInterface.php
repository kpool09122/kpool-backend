<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Slug;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

interface TalentFactoryInterface
{
    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Slug $slug
     * @param Language $language
     * @param TalentName $name
     * @return Talent
     */
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Slug                     $slug,
        Language                 $language,
        TalentName               $name,
    ): Talent;
}
