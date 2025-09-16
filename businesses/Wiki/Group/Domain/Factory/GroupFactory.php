<?php

namespace Businesses\Wiki\Group\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
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

    /**
     * @param GroupName $name
     * @return Group
     */
    public function create(
        GroupName          $name,
    ): Group {
        return new Group(
            new GroupIdentifier($this->ulidGenerator->generate()),
            $name,
            null,
            new Description(''),
            [],
            null
        );
    }
}
