<?php

namespace Businesses\Member\Domain\Factory;

use Businesses\Member\Domain\Entity\Member;
use Businesses\Member\Domain\ValueObject\Birthday;
use Businesses\Member\Domain\ValueObject\Career;
use Businesses\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Member\Domain\ValueObject\ImageLink;
use Businesses\Member\Domain\ValueObject\MemberIdentifier;
use Businesses\Member\Domain\ValueObject\MemberName;
use Businesses\Shared\Service\Ulid\UlidGeneratorInterface;

class MemberFactory implements MemberFactoryInterface
{
    public function __construct(
        private UlidGeneratorInterface $ulidGenerator,
    ) {
    }

    public function create(
        MemberName $name,
        ?GroupIdentifier $groupIdentifier,
        ?Birthday $birthday,
        Career $career,
        ?ImageLink $imageLink,
    ): Member {
        return new Member(
            new MemberIdentifier($this->ulidGenerator->generate()),
            $name,
            $groupIdentifier,
            $birthday,
            $career,
            $imageLink,
        );
    }
}
