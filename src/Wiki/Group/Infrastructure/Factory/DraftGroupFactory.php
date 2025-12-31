<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Infrastructure\Factory;

use Source\Shared\Application\Service\Uuid\UuidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\DraftGroup;
use Source\Wiki\Group\Domain\Factory\DraftGroupFactoryInterface;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\ApprovalStatus;
use Source\Wiki\Shared\Domain\ValueObject\PrincipalIdentifier;

class DraftGroupFactory implements DraftGroupFactoryInterface
{
    public function __construct(
        private UuidGeneratorInterface        $generator,
        private NormalizationServiceInterface $normalizationService,
    ) {
    }

    /**
     * @param PrincipalIdentifier $editorIdentifier
     * @param Language $language
     * @param GroupName $name
     * @param TranslationSetIdentifier|null $translationSetIdentifier 既存の翻訳セットIDがあれば指定
     * @return DraftGroup
     */
    public function create(
        PrincipalIdentifier       $editorIdentifier,
        Language                  $language,
        GroupName                 $name,
        ?TranslationSetIdentifier $translationSetIdentifier = null,
    ): DraftGroup {
        $normalizedName = $this->normalizationService->normalize((string)$name, $language);

        return new DraftGroup(
            new GroupIdentifier($this->generator->generate()),
            null,
            $translationSetIdentifier ?? new TranslationSetIdentifier($this->generator->generate()),
            $editorIdentifier,
            $language,
            $name,
            $normalizedName,
            null,
            new Description(''),
            [],
            null,
            ApprovalStatus::Pending,
        );
    }
}
