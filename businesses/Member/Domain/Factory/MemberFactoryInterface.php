<?php

namespace Businesses\Member\Domain\Factory;

use Businesses\Member\Domain\Entity\Member;
use Businesses\Member\Domain\ValueObject\Birthday;
use Businesses\Member\Domain\ValueObject\Career;
use Businesses\Member\Domain\ValueObject\GroupIdentifier;
use Businesses\Member\Domain\ValueObject\MemberName;
use Businesses\Shared\ValueObject\ImageLink;

interface MemberFactoryInterface
{
    public function create(
        MemberName $name,
        ?GroupIdentifier $groupIdentifier,
        ?Birthday $birthday,
        Career $career,
        ?ImageLink $imageLink,
    ): Member;
}
