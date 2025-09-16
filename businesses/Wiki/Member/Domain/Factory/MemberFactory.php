<?php

namespace Businesses\Wiki\Member\Domain\Factory;

use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;
use Businesses\Wiki\Member\Domain\Entity\Member;
use Businesses\Wiki\Member\Domain\ValueObject\Career;
use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Wiki\Member\Domain\ValueObject\MemberName;
use Businesses\Wiki\Member\Domain\ValueObject\RelevantVideoLinks;

readonly class MemberFactory implements MemberFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        MemberName $name,
    ): Member {
        return new Member(
            new MemberIdentifier($this->ulidGenerator->generate()),
            $name,
            [],
            null,
            new Career(''),
            null,
            new RelevantVideoLinks([]),
        );
    }
}
