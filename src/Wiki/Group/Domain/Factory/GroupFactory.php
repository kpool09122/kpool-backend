<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Shared\Domain\ValueObject\TranslationSetIdentifier;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;
use Source\Wiki\Shared\Domain\ValueObject\Version;

readonly class GroupFactory implements GroupFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    /**
     * @param TranslationSetIdentifier $translationSetIdentifier
     * @param Translation $translation
     * @param GroupName $name
     * @return Group
     */
    public function create(
        TranslationSetIdentifier $translationSetIdentifier,
        Translation $translation,
        GroupName $name,
    ): Group {
        return new Group(
            new GroupIdentifier($this->ulidGenerator->generate()),
            $translationSetIdentifier,
            $translation,
            $name,
            null,
            new Description(''),
            [],
            null,
            new Version(1),
        );
    }
}
