<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\EditorIdentifier;

readonly class DraftGroupFactory implements DraftGroupFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    /**
     * @param EditorIdentifier $editorIdentifier
     * @param Language $language
     * @param GroupName $name
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @return DraftGroup
     */
    public function create(
        EditorIdentifier          $editorIdentifier,
        Language                  $language,
        GroupName                 $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftGroup {
        return new DraftGroup(
            new GroupIdentifier($this->ulidGenerator->generate()),
            null,
            $translationSetIdentifier ?? new TranslationSetIdentifier($this->ulidGenerator->generate()),
            $editorIdentifier,
            $language,
            $name,
            null,
            new Description(''),
            [],
            null,
            ApprovalStatus::Pending,
        );
    }
}
