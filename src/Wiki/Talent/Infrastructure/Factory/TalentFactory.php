<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\Version;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\TalentFactoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

readonly class TalentFactory implements TalentFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Language $language
     * @param TalentName $name
     * @return Talent
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Language                 $language,
        TalentName               $name,
    ): Talent {
        return new Talent(
            new TalentIdentifier($this->generator->generate()),
            $translationSetIdentifier,
            $language,
            $name,
            new RealName(''),
            null,
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            new Version(1),
        );
    }
}
