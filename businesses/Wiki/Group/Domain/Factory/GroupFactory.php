<?php

namespace Businesses\Wiki\Group\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
use Businesses\Shared\ValueObject\Translation;
use Businesses\Wiki\Group\Domain\Entity\Group;
use Businesses\Wiki\Group\Domain\ValueObject\Description;
use Businesses\Wiki\Group\Domain\ValueObject\GroupIdentifier;
use Businesses\Wiki\Group\Domain\ValueObject\GroupName;

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
