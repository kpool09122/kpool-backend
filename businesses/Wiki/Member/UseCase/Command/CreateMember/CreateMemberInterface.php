<?php

namespace Businesses\Wiki\Member\UseCase\Command\CreateMember;

use Businesses\Wiki\Member\Domain\Entity\Member;

interface CreateMemberInterface
{
    public function process(CreateMemberInputPort $input): Member;
}
