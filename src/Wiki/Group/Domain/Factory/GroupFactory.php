<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Language;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\Service\NormalizationServiceInterface;
use Source\Wiki\Shared\Domain\ValueObject\Version;

readonly class GroupFactory implements GroupFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
        private NormalizationServiceInterface $normalizationService,
    ) {
    }

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
    ): Group {
        $normalizedName = $this->normalizationService->normalize((string)$name, $language);

        return new Group(
            new GroupIdentifier($this->ulidGenerator->generate()),
            $translationSetIdentifier,
            $language,
            $name,
            $normalizedName,
            null,
            new Description(''),
            [],
            null,
            new Version(1),
        );
    }
}
