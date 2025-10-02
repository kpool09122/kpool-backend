<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Domain\Factory;

use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Member\Domain\Entity\Member;
use Source\Wiki\Member\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Member\Domain\ValueObject\MemberName;
use Source\Wiki\Shared\Domain\ValueObject\TranslationSetIdentifier;

interface MemberFactoryInterface
{
    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Translation $translation
     * @param MemberName $name
     * @return Member
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Translation $translation,
        MemberName $name,
    ): Member;
}
