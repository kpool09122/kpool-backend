<?php

declare(strict_types=1);

namespace Source\Wiki\Member\Domain\Repository;

use Source\Wiki\Member\Domain\Entity\Member;
use Source\Wiki\Member\Domain\ValueObject\MemberIdentifier;

interface MemberRepositoryInterface
{
    public function findById(MemberIdentifier $identifier): ?Member;

    public function save(Member $member): void;
}
