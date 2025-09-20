<?php

declare(strict_types=1);

namespace Source\Wiki\Group\Domain\Factory;

use Source\Shared\Application\Service\Ulid\UlidGeneratorInterface;
use Source\Shared\Domain\ValueObject\Translation;
use Source\Wiki\Group\Domain\Entity\Group;
use Source\Wiki\Group\Domain\ValueObject\Description;
use Source\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Source\Wiki\Group\Domain\ValueObject\GroupName;

readonly class GroupFactory implements GroupFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        Translation $translation,
        GroupName $name,
    ): Group {
        return new Group(
            new GroupIdentifier($this->ulidGenerator->generate()),
            $translation,
            $name,
            null,
            new Description(''),
            [],
            null
        );
    }
}
