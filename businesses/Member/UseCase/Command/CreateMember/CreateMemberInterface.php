<?php

namespace Businesses\Member\UseCase\Command\CreateMember;

use Businesses\Member\Domain\Entity\Member;

interface CreateMemberInterface
{
    public function process(CreateMemberInputPort $input): ?Member;
}
