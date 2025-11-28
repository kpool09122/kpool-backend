<?php

declare(strict_types=1);

namespace Source\Wiki\Talent\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;
use Source\Wiki\Talent\Domain\Entity\DraftTalent;
use Source\Wiki\Talent\Domain\Exception\ExceedMaxRelevantVideoLinksException;
use Source\Wiki\Talent\Domain\ValueObject\Career;
use Source\Wiki\Talent\Domain\ValueObject\RealName;
use Source\Wiki\Talent\Domain\ValueObject\RelevantVideoLinks;
use Source\Wiki\Talent\Domain\ValueObject\TalentIdentifier;
use Source\Wiki\Talent\Domain\ValueObject\TalentName;

readonly class DraftTalentFactory implements DraftTalentFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    /**
     * @param EditorIdentifier $editorIdentifier
     * @param Language $language
     * @param TalentName $name
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @return DraftTalent
     * @throws ExceedMaxRelevantVideoLinksException
     */
    public function create(
        EditorIdentifier          $editorIdentifier,
        Language                  $language,
        TalentName                $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftTalent {
        return new DraftTalent(
            new TalentIdentifier($this->ulidGenerator->generate()),
            null,
            $translationSetIdentifier ?? new TranslationSetIdentifier($this->ulidGenerator->generate()),
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
