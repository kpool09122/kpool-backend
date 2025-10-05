<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

interface TalentFactoryInterface
{
    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Translation $translation
     * @param TalentName $name
     * @return Talent
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Translation $translation,
        TalentName $name,
    ): Talent;
}
