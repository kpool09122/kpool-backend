<?php

namespace Businesses\Member\Domain\Repository;

use Businesses\Member\Domain\Entity\Member;
use Businesses\Member\Domain\ValueObject\MemberIdentifier;

interface MemberRepositoryInterface
{
    public function findById(MemberIdentifier $identifier): ?Member;

    public function save(Member $member): void;
}
