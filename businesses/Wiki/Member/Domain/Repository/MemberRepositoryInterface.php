<?php

declare(strict_types=1);

namespace Businesses\Wiki\Member\Domain\Repository;

use Businesses\Wiki\Member\Domain\Entity\Member;
use Businesses\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface MemberRepositoryInterface
{
    public function findById(MemberIdentifier $identifier): ?Member;

    public function save(Member $member): void;
}
