<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Talent\Domain\Entity\Talent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

readonly class TalentFactory implements TalentFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

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
    ): Talent {
        return new Talent(
            new TalentIdentifier($this->ulidGenerator->generate()),
            $translationSetIdentifier,
            $translation,
            $name,
            new RealName(''),
            null,
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
        );
    }
}
