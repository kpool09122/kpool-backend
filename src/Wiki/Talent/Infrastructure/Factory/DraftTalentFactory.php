<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\Factory\DraftTalentFactoryInterface;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

readonly class DraftTalentFactory implements DraftTalentFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface $generator,
    ) {
    }

    /**
     * @param PrincipalIdentifier $editorIdentifier
     * @param Language $language
     * @param TalentName $name
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @return DraftTalent
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        PrincipalIdentifier       $editorIdentifier,
        Language                  $language,
        TalentName                $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftTalent {
        return new DraftTalent(
            new TalentIdentifier($this->generator->generate()),
            null,
            $translationSetIdentifier ?? new TranslationSetIdentifier($this->generator->generate()),
            $editorIdentifier,
            $language,
            $name,
            new RealName(''),
            null,
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
            ApprovalStatus::Pending,
        );
    }
}
