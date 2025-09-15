<?php

namespace Businesses\Group\Domain\Factory;

use Businesses\Group\Domain\Entity\Group;
use Businesses\Group\Domain\ValueObject\CompanyIdentifier;
use Businesses\Group\Domain\ValueObject\Description;
use Businesses\Group\Domain\ValueObject\GroupIdentifier;
use Businesses\Group\Domain\ValueObject\GroupName;
use Businesses\Group\Domain\ValueObject\SongIdentifier;
use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
use Businesses\Shared\ValueObject\ImageLink;

class GroupFactory implements GroupFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    /**
     * @param GroupName $name
     * @param CompanyIdentifier|null $companyIdentifier
     * @param Description $description
     * @param list<SongIdentifier> $songIdentifiers
     * @param ImageLink|null $imageLink
     * @return Group
     */
    public function create(
        GroupName $name,
        ?CompanyIdentifier $companyIdentifier,
        Description $description,
        array $songIdentifiers,
        ?ImageLink $imageLink,
    ): Group {
        return new Group(
            new GroupIdentifier($this->ulidGenerator->generate()),
            $name,
            $companyIdentifier,
            $description,
            $songIdentifiers,
            $imageLink
        );
    }
}
